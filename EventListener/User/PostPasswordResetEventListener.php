<?php

namespace Netgen\Bundle\MoreBundle\EventListener\User;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Netgen\Bundle\MoreBundle\Event\User\PostPasswordResetEvent;
use Netgen\Bundle\MoreBundle\Helper\MailHelper;
use Netgen\Bundle\MoreBundle\Entity\Repository\EzUserAccountKeyRepository;

class PostPasswordResetEventListener
{
    /**
     * @var \Netgen\Bundle\MoreBundle\Helper\MailHelper
     */
    protected $mailHelper;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var \Netgen\Bundle\MoreBundle\Entity\Repository\EzUserAccountKeyRepository;
     */
    protected $ezUserAccountKeyRepository;

    /**
     * @param MailHelper $mailHelper
     * @param ConfigResolverInterface $configResolver
     * @param \Netgen\Bundle\MoreBundle\Entity\Repository\EzUserAccountKeyRepository
     */
    public function __construct(
        MailHelper $mailHelper,
        ConfigResolverInterface $configResolver,
        EzUserAccountKeyRepository $ezUserAccountKeyRepository
    )
    {
        $this->mailHelper = $mailHelper;
        $this->configResolver = $configResolver;
        $this->ezUserAccountKeyRepository = $ezUserAccountKeyRepository;
    }

    /**
     * Listens to the event triggered after the password has been reset.
     * Event contains the information about the user who has changed the password.
     *
     * @param \Netgen\Bundle\MoreBundle\Event\User\PostPasswordResetEvent $event
     */
    public function onPasswordReset( PostPasswordResetEvent $event )
    {
        $this->mailHelper
            ->sendMail(
                $event->getUser()->email,
                $this->configResolver->getParameter( 'template.user.mail.forgot_password_password_changed', 'ngmore' ),
                'ngmore.user.forgot_password.password_changed.subject',
                array(
                    'user' => $event->getUser()
                )
            );

        $this->ezUserAccountKeyRepository->removeByUserId( $event->getUser()->id );

    }
}
