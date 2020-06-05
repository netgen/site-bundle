<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Helper;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use function array_key_first;
use function count;
use function get_debug_type;
use function is_array;
use function is_string;
use function sprintf;

class MailHelper
{
    /**
     * @var \Symfony\Component\Mailer\MailerInterface
     */
    protected $mailer;

    /**
     * @var \Twig\Environment
     */
    protected $twig;

    /**
     * @var \Symfony\Contracts\Translation\TranslatorInterface
     */
    protected $translator;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var \Netgen\Bundle\SiteBundle\Helper\SiteInfoHelper
     */
    protected $siteInfoHelper;

    /**
     * @var string
     */
    protected $siteUrl;

    /**
     * @var string
     */
    protected $siteName;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        MailerInterface $mailer,
        Environment $twig,
        TranslatorInterface $translator,
        ConfigResolverInterface $configResolver,
        SiteInfoHelper $siteInfoHelper,
        ?LoggerInterface $logger = null
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->translator = $translator;
        $this->configResolver = $configResolver;
        $this->siteInfoHelper = $siteInfoHelper;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Sends an mail.
     *
     * Receivers can be:
     * a string: info@netgen.io
     * or:
     * array( 'info@netgen.io' => 'Netgen Site' ) or
     * array( 'info@netgen.io', 'example@netgen.io' ) or
     * array( 'info@netgen.io' => 'Netgen Site', 'example@netgen.io' => 'Example' )
     *
     * Sender can be:
     * a string: info@netgen.io
     * an array: array( 'info@netgen.io' => 'Netgen Site' )
     *
     * @param string|array $receivers
     * @param string|array|null $sender
     */
    public function sendMail($receivers, string $subject, string $template, array $parameters = [], $sender = null): void
    {
        try {
            $senderAddress = $this->createSenderAddress($sender);
        } catch (InvalidArgumentException $e) {
            $this->logger->error($e->getMessage());

            return;
        }

        $email = (new Email())
            ->from($senderAddress)
            ->sender($senderAddress)
            ->to(...$this->createReceiverAddresses($receivers))
            ->subject(sprintf('%s: %s', $this->siteName, $this->translator->trans($subject, [], 'ngsite_mail')))
            ->html($this->twig->render($template, $parameters));

        $this->mailer->send($email);
    }

    /**
     * Creates a sender address from provided value.
     * If sender is not provided (if it is null), it attempts to get the sender from the parameters:
     * ngsite.default.mail.sender_email
     * ngsite.default.mail.sender_name (optional).
     *
     * @param string|array|null $sender
     *
     * @throws \InvalidArgumentException If sender was not provided
     */
    protected function createSenderAddress($sender): Address
    {
        if (!empty($sender)) {
            if ((is_array($sender) && count($sender) === 1 && !isset($sender[0])) || is_string($sender)) {
                if (is_array($sender)) {
                    return new Address(array_key_first($sender), $sender[array_key_first($sender)]);
                }

                return new Address($sender);
            }

            throw new InvalidArgumentException(
                "Parameter 'sender' has to be either a string, or an associative array with one element (e.g. array( 'info@example.com' => 'Example name' )), {$sender} given."
            );
        }

        if ($this->configResolver->hasParameter('mail.sender_email', 'ngsite')) {
            $name = $this->configResolver->hasParameter('mail.sender_name', 'ngsite') ?
                $this->configResolver->getParameter('mail.sender_name', 'ngsite') :
                '';

            return new Address(
                $this->configResolver->getParameter('mail.sender_email', 'ngsite'),
                $name
            );
        }

        throw new InvalidArgumentException("Parameter 'sender' has not been provided, nor it has been configured via parameters ('ngsite.default.mail.sender_email')!");
    }

    /**
     * @param string|array $addresses
     *
     * @return iterable<\Symfony\Component\Mime\Address>
     */
    private function createReceiverAddresses($addresses): iterable
    {
        if (!is_string($addresses) && !is_array($addresses)) {
            $this->logger->error(
                sprintf(
                    'Invalid address format. Required string or array, given %s',
                    get_debug_type($addresses)
                )
            );
        }

        foreach ((array) $addresses as $key => $value) {
            if (is_string($key)) {
                yield new Address($key, $value);

                continue;
            }

            yield new Address($value);
        }
    }
}
