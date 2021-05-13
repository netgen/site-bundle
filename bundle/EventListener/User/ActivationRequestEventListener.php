<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\EventListener\User;

use eZ\Publish\API\Repository\Values\User\User;
use Netgen\Bundle\SiteBundle\Event\SiteEvents;
use Netgen\Bundle\SiteBundle\Event\User\ActivationRequestEvent;
use Netgen\Bundle\SiteBundle\EventListener\UserEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ActivationRequestEventListener extends UserEventListener implements EventSubscriberInterface
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SiteEvents::USER_ACTIVATION_REQUEST => 'onActivationRequest',
        ];
    }

    /**
     * Listens for the start of the activation process.
     * Event contains information about the submitted email and the user, if found.
     */
    public function onActivationRequest(ActivationRequestEvent $event): void
    {
        $user = $event->getUser();
        $email = $event->getEmail();

        if (!$user instanceof User) {
            $this->mailHelper->sendMail(
                $email,
                'ngsite.user.activate.not_registered.subject',
                $this->configResolver->getParameter('template.user.mail.activate_not_registered', 'ngsite'),
            );

            return;
        }

        if ($user->enabled) {
            $this->mailHelper->sendMail(
                [$user->email => $this->getUserName($user)],
                'ngsite.user.activate.already_active.subject',
                $this->configResolver->getParameter('template.user.mail.activate_already_active', 'ngsite'),
                [
                    'user' => $user,
                ],
            );

            return;
        }

        if ($this->ngUserSettingRepository->isUserActivated($user->id)) {
            $this->mailHelper->sendMail(
                [$user->email => $this->getUserName($user)],
                'ngsite.user.activate.disabled.subject',
                $this->configResolver->getParameter('template.user.mail.activate_disabled', 'ngsite'),
                [
                    'user' => $user,
                ],
            );

            return;
        }

        $accountKey = $this->ezUserAccountKeyRepository->create($user->id);

        $this->mailHelper
            ->sendMail(
                [$user->email => $this->getUserName($user)],
                'ngsite.user.activate.subject',
                $this->configResolver->getParameter('template.user.mail.activate', 'ngsite'),
                [
                    'user' => $user,
                    'hash' => $accountKey->getHash(),
                ],
            );
    }
}
