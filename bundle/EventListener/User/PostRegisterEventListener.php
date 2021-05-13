<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\EventListener\User;

use Netgen\Bundle\SiteBundle\Event\SiteEvents;
use Netgen\Bundle\SiteBundle\Event\User\PostRegisterEvent;
use Netgen\Bundle\SiteBundle\EventListener\UserEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PostRegisterEventListener extends UserEventListener implements EventSubscriberInterface
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SiteEvents::USER_POST_REGISTER => 'onUserRegistered',
        ];
    }

    /**
     * Listens to the event triggered after the user has been registered.
     * The event contains information about registered user.
     */
    public function onUserRegistered(PostRegisterEvent $event): void
    {
        $user = $event->getUser();

        if ($user->enabled) {
            $this->mailHelper
                ->sendMail(
                    [$user->email => $this->getUserName($user)],
                    'ngsite.user.welcome.subject',
                    $this->configResolver->getParameter('template.user.mail.welcome', 'ngsite'),
                    [
                        'user' => $user,
                    ],
                );

            return;
        }

        $accountKey = $this->ezUserAccountKeyRepository->create($user->id);

        if ($this->configResolver->getParameter('user.require_admin_activation', 'ngsite')) {
            $this->mailHelper
                ->sendMail(
                    [$user->email => $this->getUserName($user)],
                    'ngsite.user.activate.admin_activation_pending.subject',
                    $this->configResolver->getParameter('template.user.mail.activate_admin_activation_pending', 'ngsite'),
                    [
                        'user' => $user,
                    ],
                );

            $adminEmail = $this->configResolver->getParameter('user.mail.admin_email', 'ngsite');
            $adminName = $this->configResolver->getParameter('user.mail.admin_name', 'ngsite');

            if (!empty($adminEmail)) {
                $this->mailHelper
                    ->sendMail(
                        !empty($adminName) ? [$adminEmail => $adminName] : [$adminEmail],
                        'ngsite.user.activate.admin_activation_required.subject',
                        $this->configResolver->getParameter('template.user.mail.activate_admin_activation_required', 'ngsite'),
                        [
                            'user' => $user,
                        ],
                    );
            }

            return;
        }

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
