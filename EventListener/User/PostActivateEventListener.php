<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\EventListener\User;

use Netgen\Bundle\MoreBundle\Event\MVCEvents;
use Netgen\Bundle\MoreBundle\Event\User\PostActivateEvent;
use Netgen\Bundle\MoreBundle\EventListener\UserEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PostActivateEventListener extends UserEventListener implements EventSubscriberInterface
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            MVCEvents::USER_POST_ACTIVATE => 'onPostActivate',
        ];
    }

    /**
     * Listens to the event triggered after the user activation has been finished.
     * Event contains information about activated user.
     */
    public function onPostActivate(PostActivateEvent $event): void
    {
        $user = $event->getUser();

        $this->ezUserAccountKeyRepository->removeByUserId($user->id);
        $this->ngUserSettingRepository->activateUser($user->id);

        $this->mailHelper
            ->sendMail(
                [$user->email => $this->getUserName($user)],
                'ngmore.user.welcome.subject',
                $this->configResolver->getParameter('template.user.mail.welcome', 'ngmore'),
                [
                    'user' => $user,
                ]
            );
    }
}
