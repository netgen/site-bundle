<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\EventListener\User;

use Netgen\Bundle\SiteBundle\Event\SiteEvents;
use Netgen\Bundle\SiteBundle\Event\User\PostActivateEvent;
use Netgen\Bundle\SiteBundle\EventListener\UserEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PostActivateEventListener extends UserEventListener implements EventSubscriberInterface
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SiteEvents::USER_POST_ACTIVATE => 'onPostActivate',
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
                'ngsite.user.welcome.subject',
                $this->configResolver->getParameter('template.user.mail.welcome', 'ngsite'),
                [
                    'user' => $user,
                ],
            );
    }
}
