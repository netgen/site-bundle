<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\EventListener\User;

use Netgen\Bundle\SiteBundle\Event\SiteEvents;
use Netgen\Bundle\SiteBundle\Event\User\PostPasswordResetEvent;
use Netgen\Bundle\SiteBundle\EventListener\UserEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PostPasswordResetEventListener extends UserEventListener implements EventSubscriberInterface
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SiteEvents::USER_POST_PASSWORD_RESET => 'onPasswordReset',
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
                'ngsite.user.forgot_password.password_changed.subject',
                $this->configResolver->getParameter('template.user.mail.forgot_password_password_changed', 'ngsite'),
                [
                    'user' => $user,
                ],
            );

        $this->ezUserAccountKeyRepository->removeByUserId($user->id);
    }
}
