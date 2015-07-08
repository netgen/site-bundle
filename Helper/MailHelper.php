<?php

namespace Netgen\Bundle\MoreBundle\Helper;

use Swift_Mailer;
use Swift_Message;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Routing\RouterInterface;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\API\Repository\Values\User\User;

class MailHelper
{
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

    protected $activationMailTemplate;

    protected $forgottenPasswordMailTemplate;

    protected $mailNotRegisteredMailTemplate;

    protected $passwordChangedMailTemplate;

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

        $this->activationMailTemplate =
            $configResolver->getParameter( 'user_register.template.activation_mail', 'ngmore' );

        $this->forgottenPasswordMailTemplate =
            $configResolver->getParameter( 'user_register.template.forgotten_password_mail', 'ngmore' );

        $this->mailNotRegisteredMailTemplate =
            $configResolver->getParameter( 'user_register.template.mail_not_registered_mail', 'ngmore' );

        $this->passwordChangedMailTemplate =
            $configResolver->getParameter( 'user_register.template.password_changed_mail', 'ngmore' );

        $rootLocationId = $configResolver->getParameter( 'content.tree_root.location_id' );
        $this->baseUrl = $this->router->generate( 'ez_urlalias', array( "locationId" => $rootLocationId ) );
        $this->siteName = $configResolver->getParameter( 'SiteSettings.SiteName' );
    }

    public function sendPasswordChangedMail( $user, $returnUrl = null, $subject = null )
    {
        $emailTo = $user->email;
        $templateContent = $this->twig->loadTemplate( $this->passwordChangedMailTemplate );
        $body = $templateContent->render(
            array(
                'user' => $user,
                'base_url' => $this->baseUrl,
                'site_name' => $this->siteName,
                 'return_url' => $returnUrl
            )
        );
        $subject = $subject ?: $this->translator->trans( "ngmore.user.mail.password_changed.subject" );

        $message = Swift_Message::newInstance()
                                ->setSubject( $subject )
                                ->setFrom( $this->fromMailAddress, $this->siteName )
                                ->setTo( $emailTo )
                                ->setBody( $body, 'text/html' )
        ;
        return $this->mailer->send( $message );
    }

    /**
     * Sends activation mail
     *
     * @param User $user
     * @param $hash
     * @param null|string $subject
     *
     * @return int
     */
    public function sendActivationMail( User $user, $hash, $subject = null )
    {
        $emailTo = $user->email;
        $templateContent = $this->twig->loadTemplate( $this->activationMailTemplate );
        $body = $templateContent->render(
            array(
                'user' => $user,
                'base_url' => $this->baseUrl,
                'site_name' => $this->siteName,
                'hash' => $hash
            )
        );
        $subject = $subject ?: $this->translator->trans( "ngmore.user.mail.activation.subject" );

        $message = Swift_Message::newInstance()
                                ->setSubject( $subject )
                                ->setFrom( $this->fromMailAddress, $this->siteName )
                                ->setTo( $emailTo )
                                ->setBody( $body, 'text/html' )
        ;
        return $this->mailer->send( $message );
    }

    /**
     * Sends forgotten password mail
     *
     * @param User $user
     * @param string $hash
     * @param string $subject
     *
     * @return int
     */
    public function sendChangePasswordMail( User $user, $hash, $subject = null )
    {
        $templateContent = $this->twig->loadTemplate( $this->forgottenPasswordMailTemplate );
        $body = $templateContent->render(
            array(
                'user' => $user,
                'hash' => $hash
            )
        );

        $subject = $subject ?: $this->translator->trans( "ngmore.user.mail.forgotten_password.subject" );;
        $message = Swift_Message::newInstance()
                                ->setSubject( $subject )
                                ->setFrom( $this->fromMailAddress, $this->siteName )
                                ->setTo( $user->email )
                                ->setBody( $body, 'text/html' )
        ;
        return $this->mailer->send( $message );
    }

    public function sendEmailNotRegisteredMail( $email, $returnUrl = null, $subject = null )
    {
        $templateContent = $this->twig->loadTemplate( $this->mailNotRegisteredMailTemplate );
        $body = $templateContent->render(
            array(
                'base_url' => $this->baseUrl,
                'site_name' => $this->siteName,
                'returnUrl' => $returnUrl
            )
        );

        $subject = $subject ?: $this->translator->trans( "ngmore.user.mail.email_not_registered.subject" );
        $message = Swift_Message::newInstance()
                                ->setSubject( $subject )
                                ->setFrom( $this->fromMailAddress, $this->siteName )
                                ->setTo( $email )
                                ->setBody( $body, 'text/html' )
        ;
        return $this->mailer->send( $message );
    }
}