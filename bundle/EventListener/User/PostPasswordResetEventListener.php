<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\EventListener\User;

use Netgen\Bundle\MoreBundle\Event\NetgenMoreEvents;
use Netgen\Bundle\MoreBundle\Event\User\PostPasswordResetEvent;
use Netgen\Bundle\MoreBundle\EventListener\UserEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PostPasswordResetEventListener extends UserEventListener implements EventSubscriberInterface
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            NetgenMoreEvents::USER_POST_PASSWORD_RESET => 'onPasswordReset',
        ];
    }

    /**
     * Listens to the event triggered after the password has been reset.
     * Event contains the information about the user who has changed the password.
     */
    public function onPasswordReset(PostPasswordResetEvent $event): void
    {
        $user = $event->getUser();

        $this->mailHelper
            ->sendMail(
                [$user->email => $this->getUserName($user)],
                'ngmore.user.forgot_password.password_changed.subject',
                $this->configResolver->getParameter('template.user.mail.forgot_password_password_changed', 'ngmore'),
                [
                    'user' => $user,
                ]
            );

        $this->ezUserAccountKeyRepository->removeByUserId($user->id);
    }
}
