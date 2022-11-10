<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Helper;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Twig\Environment;

use function count;
use function is_array;
use function is_string;
use function trim;

class MailHelper
{
    protected Swift_Mailer $mailer;

    protected Environment $twig;

    protected UrlGeneratorInterface $urlGenerator;

    protected TranslatorInterface $translator;

    protected ConfigResolverInterface $configResolver;

    protected SiteInfoHelper $siteInfoHelper;

    protected LoggerInterface $logger;

    protected string $siteUrl;

    protected string $siteName;

    public function __construct(
        Swift_Mailer $mailer,
        Environment $twig,
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator,
        ConfigResolverInterface $configResolver,
        SiteInfoHelper $siteInfoHelper,
        ?LoggerInterface $logger = null
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->urlGenerator = $urlGenerator;
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
     * @param mixed $receivers
     * @param mixed|null $sender
     */
    public function sendMail($receivers, string $subject, string $template, array $templateParameters = [], $sender = null): int
    {
        try {
            $sender = $this->getSender($sender);
        } catch (InvalidArgumentException $e) {
            $this->logger->error($e->getMessage());

            return -1;
        }

        $body = $this->twig->render($template, $templateParameters + $this->getDefaultTemplateParameters());

        $subject = $this->translator->trans($subject, [], 'ngsite_mail');

        $message = new Swift_Message();

        $message
            ->setTo($receivers)
            ->setSubject($this->siteName . ': ' . $subject)
            ->setBody($body, 'text/html');

        $message->setSender($sender);
        $message->setFrom($sender);

        return $this->mailer->send($message);
    }

    /**
     * Returns an array of parameters that will be passed to every mail template.
     */
    protected function getDefaultTemplateParameters(): array
    {
        $this->siteUrl ??= $this->urlGenerator->generate(
            'ez_urlalias',
            [
                'locationId' => $this->configResolver->getParameter('content.tree_root.location_id'),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $this->siteName ??= trim($this->siteInfoHelper->getSiteInfoContent()->getField('site_name')->value->text);

        return [
            'site_url' => $this->siteUrl,
            'site_name' => $this->siteName,
        ];
    }

    /**
     * Validates the sender parameter.
     * If sender not provided, it attempts to get the sender from the parameters:
     * ngsite.default.mail.sender_email
     * ngsite.default.mail.sender_name (optional).
     *
     * @param mixed $sender
     *
     * @return array|string
     *
     * @throws \InvalidArgumentException If sender was not provided
     */
    protected function getSender($sender)
    {
        if (!empty($sender)) {
            if ((is_array($sender) && count($sender) === 1 && !isset($sender[0])) || is_string($sender)) {
                return $sender;
            }

            throw new InvalidArgumentException(
                "Parameter 'sender' has to be either a string, or an associative array with one element (e.g. array( 'info@example.com' => 'Example name' )), {$sender} given.",
            );
        }

        if ($this->configResolver->hasParameter('mail.sender_email', 'ngsite')) {
            if ($this->configResolver->hasParameter('mail.sender_name', 'ngsite')) {
                return [
                    $this->configResolver->getParameter('mail.sender_email', 'ngsite') => $this->configResolver->getParameter('mail.sender_name', 'ngsite'),
                ];
            }

            return $this->configResolver->getParameter('mail.sender_email', 'ngsite');
        }

        throw new InvalidArgumentException("Parameter 'sender' has not been provided, nor it has been configured via parameters ('ngsite.default.mail.sender_email')!");
    }
}
