<?php

namespace Netgen\Bundle\MoreBundle\Helper;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\EngineInterface;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Swift_Mailer;
use Swift_Message;

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
     * Constructor
     *
     * @param \Swift_Mailer $mailer
     * @param \Symfony\Component\Templating\EngineInterface $templating
     * @param \Symfony\Component\Routing\RouterInterface $router
     * @param \Symfony\Component\Translation\TranslatorInterface $translator
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     */
    public function __construct(
        Swift_Mailer $mailer,
        EngineInterface $templating,
        RouterInterface $router,
        TranslatorInterface $translator,
        ConfigResolverInterface $configResolver
    )
    {
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->router = $router;
        $this->translator = $translator;
        $this->configResolver = $configResolver;

        $this->siteUrl = $this->router->generate(
            'ez_urlalias',
            array(
                "locationId" => $configResolver->getParameter( 'content.tree_root.location_id' )
            ),
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->siteName = $configResolver->getParameter( 'SiteSettings.SiteName' );
    }

    /**
     * Sends an mail
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
     * @param string $template
     * @param string $subject
     * @param array $templateParameters
     * @param mixed $sender
     *
     * @return int
     */
    public function sendMail( $receivers, $subject, $template, $templateParameters = array(), $sender = null )
    {
        $templateParameters['site_url'] = $this->siteUrl;
        $templateParameters['site_name'] = $this->siteName;

        $body = $this->templating->render( $template, $templateParameters );

        $subject = $this->translator->trans( $subject, array(), 'ngmore_mail' );

        /** @var \Swift_Mime_Message $message */
        $message = Swift_Message::newInstance();

        $message
            ->setTo( $receivers )
            ->setSubject( $this->siteName . ': ' . $subject )
            ->setBody( $body, 'text/html' );

        if ( !empty( $sender ) )
        {
            if ( ( is_array( $sender ) && count( $sender ) == 1 ) || is_string( $sender ) )
            {
                $message->setSender( $sender );
                $message->setFrom( $sender );
            }
        }
        else if ( $this->configResolver->hasParameter( 'mail.sender_email', 'ngmore' )
            && $this->configResolver->hasParameter( 'mail.sender_name', 'ngmore' ) )
        {
            $sender = array(
                $this->configResolver->getParameter( 'mail.sender_email', 'ngmore' ) =>
                $this->configResolver->getParameter( 'mail.sender_name', 'ngmore' )
            );
            $message->setSender( $sender );
            $message->setFrom( $sender );
        }

        return $this->mailer->send( $message );
    }
}
