<?php

namespace Netgen\Bundle\MoreBundle\Helper;

use Swift_Mailer;
use Swift_Message;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Routing\RouterInterface;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\API\Repository\Values\User\User;

class MailHelper
{
    const WELCOME = "welcome";
    const FORGOTTENPASSWORD= "forgottenPassword";
    const MAILNOTREGISTERED = "mailNotRegistered";
    const PASSWORDCHANGED = "passwordChanged";
    const ACTIVATION = "activation";

    /** @var \Swift_Mailer  */
    protected $mailer;

    /** @var \Twig_Environment  */
    protected $twig;

    /** @var  RouterInterface */
    protected $router;

    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    protected $configResolver;

    /** @var \Symfony\Component\Translation\TranslatorInterface */
    protected $translator;

    protected $fromMailAddress;

    protected $templates = array();

    protected $subject = array();

    protected $baseUrl;

    protected $siteName;

    public function __construct(
        \Swift_Mailer $mailer,
        \Twig_Environment $twig,
        RouterInterface $router,
        TranslatorInterface $translator,
        ConfigResolverInterface $configResolver
    )
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->router = $router;
        $this->translator = $translator;
        $this->configResolver = $configResolver;
        $this->fromMailAddress = $configResolver->getParameter( 'user_register.mail_sender', 'ngmore' );

        $this->templates = array(
            $this::ACTIVATION => $configResolver->getParameter( 'user_register.template.mail.activation', 'ngmore' ),
            $this::FORGOTTENPASSWORD => $configResolver->getParameter( 'user_register.template.mail.forgotten_password', 'ngmore' ),
            $this::MAILNOTREGISTERED => $configResolver->getParameter( 'user_register.template.mail.email_not_registered', 'ngmore' ),
            $this::PASSWORDCHANGED => $configResolver->getParameter( 'user_register.template.mail.password_changed', 'ngmore' ),
            $this::WELCOME => $configResolver->getParameter( 'user_register.template.mail.welcome', 'ngmore' )
        );

        $this->subject = array(
            $this::ACTIVATION => $this->translator->trans( "ngmore.user.mail.subject.activation" ),
            $this::FORGOTTENPASSWORD => $this->translator->trans( "ngmore.user.mail.subject.forgotten_password" ),
            $this::MAILNOTREGISTERED => $this->translator->trans( "ngmore.user.mail.subject.email_not_registered" ),
            $this::PASSWORDCHANGED => $this->translator->trans( "ngmore.user.mail.subject.password_changed" ),
            $this::WELCOME => $this->translator->trans( "ngmore.user.mail.subject.welcome" )
        );

        $rootLocationId = $configResolver->getParameter( 'content.tree_root.location_id' );
        $this->baseUrl = $this->router->generate( 'ez_urlalias', array( "locationId" => $rootLocationId ), UrlGeneratorInterface::ABSOLUTE_URL );
        $this->siteName = $configResolver->getParameter( 'SiteSettings.SiteName' );
    }

    public function sendMail( $email, $type, $templateParameters = array() )
    {
        if ( !array_key_exists( $type, $this->templates ) )
        {
            $allowedTypes = explode( ', ', array_keys( $this->templates ) );

            throw new \InvalidArgumentException( "{$type} is not supported. Mail type has to be one of the following: {$allowedTypes}" );
        }

        $templateParameters['base_url'] = $this->baseUrl;
        $templateParameters['site_name'] = $this->siteName;

        $templateContent = $this->twig->loadTemplate( $this->templates[$type] );
        $body = $templateContent->render( $templateParameters );

        $subject = $templateParameters['subject'] ?: $this->subject[$type];

        $message = Swift_Message::newInstance()
            ->setFrom( $this->fromMailAddress, $this->siteName )
            ->setTo( $email )
            ->setSubject( $subject )
            ->setBody( $body, 'text/html' );

        return $this->mailer->send( $message );
    }
}