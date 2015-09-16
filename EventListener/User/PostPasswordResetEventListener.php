<?php

namespace Netgen\Bundle\MoreBundle\EventListener\User;

use Netgen\Bundle\MoreBundle\EventListener\UserEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Netgen\Bundle\MoreBundle\Event\MVCEvents;
use Netgen\Bundle\MoreBundle\Event\User\PostPasswordResetEvent;

class PostPasswordResetEventListener extends UserEventListener implements EventSubscriberInterface
{
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

    public static function getSubscribedEvents()
    {
        return array(
            MVCEvents::USER_POST_PASSWORD_RESET => 'onPasswordReset'
        );
    }
}
