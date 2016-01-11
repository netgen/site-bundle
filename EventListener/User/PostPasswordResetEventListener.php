<?php

namespace Netgen\Bundle\MoreBundle\EventListener\User;

use Netgen\Bundle\MoreBundle\EventListener\UserEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Netgen\Bundle\MoreBundle\Event\MVCEvents;
use Netgen\Bundle\MoreBundle\Event\User\PostPasswordResetEvent;

class PostPasswordResetEventListener extends UserEventListener implements EventSubscriberInterface
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            MVCEvents::USER_POST_PASSWORD_RESET => 'onPasswordReset',
        );
    }

    /**
     * Listens to the event triggered after the password has been reset.
     * Event contains the information about the user who has changed the password.
     *
     * @param \Netgen\Bundle\MoreBundle\Event\User\PostPasswordResetEvent $event
     */
    public function onPasswordReset(PostPasswordResetEvent $event)
    {
        $user = $event->getUser();

        $this->mailHelper
            ->sendMail(
                array($user->email => $this->translationHelper->getTranslatedContentName($user)),
                'ngmore.user.forgot_password.password_changed.subject',
                $this->configResolver->getParameter('template.user.mail.forgot_password_password_changed', 'ngmore'),
                array(
                    'user' => $user,
                )
            );

        $this->ezUserAccountKeyRepository->removeByUserId($user->id);
    }
}
