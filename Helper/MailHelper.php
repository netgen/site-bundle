<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Helper;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

class MailHelper
{
    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var \Symfony\Component\Templating\EngineInterface
     */
    protected $templating;

    /**
     * @var  \Symfony\Component\Routing\RouterInterface
     */
    protected $router;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

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
        Swift_Mailer $mailer,
        EngineInterface $templating,
        RouterInterface $router,
        TranslatorInterface $translator,
        ConfigResolverInterface $configResolver,
        LoggerInterface $logger = null
    ) {
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->router = $router;
        $this->translator = $translator;
        $this->configResolver = $configResolver;
        $this->logger = $logger ?: new NullLogger();

        $this->siteUrl = $this->router->generate(
            'ez_urlalias',
            [
                'locationId' => $configResolver->getParameter('content.tree_root.location_id'),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->siteName = $configResolver->getParameter('site_settings.site_name', 'ngmore');
    }

    /**
     * Sends an mail.
     *
     * Receivers can be:
     * a string: info@netgen.hr
     * or:
     * array( 'info@netgen.hr' => 'Netgen More' ) or
     * array( 'info@netgen.hr', 'example@netgen.hr' ) or
     * array( 'info@netgen.hr' => 'Netgen More', 'example@netgen.hr' => 'Example' )
     *
     * Sender can be:
     * a string: info@netgen.hr
     * an array: array( 'info@netgen.hr' => 'Netgen More' )
     *
     * @param mixed $receivers
     * @param null|mixed $sender
     */
    public function sendMail($receivers, string $subject, string $template, array $templateParameters = [], $sender = null): int
    {
        try {
            $sender = $this->getSender($sender);
        } catch (InvalidArgumentException $e) {
            $this->logger->error($e->getMessage());

            return -1;
        }

        $body = $this->templating->render($template, $templateParameters + $this->getDefaultTemplateParameters());

        $subject = $this->translator->trans($subject, [], 'ngmore_mail');

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
        return [
            'site_url' => $this->siteUrl,
            'site_name' => $this->siteName,
        ];
    }

    /**
     * Validates the sender parameter.
     * If sender not provided, it attempts to get the sender from the parameters:
     * ngmore.default.mail.sender_email
     * ngmore.default.mail.sender_name (optional).
     *
     *
     * @param mixed $sender
     *
     * @throws \InvalidArgumentException If sender was not provided
     *
     * @return array|string
     */
    protected function getSender($sender)
    {
        if (!empty($sender)) {
            if ((is_array($sender) && count($sender) === 1 && !isset($sender[0])) || is_string($sender)) {
                return $sender;
            }

            throw new InvalidArgumentException(
                "Parameter 'sender' has to be either a string, or an associative array with one element (e.g. array( 'info@example.com' => 'Example name' )), {$sender} given."
            );
        } elseif ($this->configResolver->hasParameter('mail.sender_email', 'ngmore')) {
            if ($this->configResolver->hasParameter('mail.sender_name', 'ngmore')) {
                return [
                    $this->configResolver->getParameter('mail.sender_email', 'ngmore') => $this->configResolver->getParameter('mail.sender_name', 'ngmore'),
                ];
            }

            return $this->configResolver->getParameter('mail.sender_email', 'ngmore');
        }

        throw new InvalidArgumentException("Parameter 'sender' has not been provided, nor it has been configured via parameters ('ngmore.default.mail.sender_email')!");
    }
}
