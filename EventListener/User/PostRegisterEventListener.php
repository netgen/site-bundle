<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\EventListener\User;

use Netgen\Bundle\MoreBundle\Event\MVCEvents;
use Netgen\Bundle\MoreBundle\Event\User\PostRegisterEvent;
use Netgen\Bundle\MoreBundle\EventListener\UserEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PostRegisterEventListener extends UserEventListener implements EventSubscriberInterface
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            MVCEvents::USER_POST_REGISTER => 'onUserRegistered',
        );
    }

    /**
     * Listens to the event triggered after the user has been registered.
     * The event contains information about registered user.
     *
     * @param \Netgen\Bundle\MoreBundle\Event\User\PostRegisterEvent $event
     */
    public function onUserRegistered(PostRegisterEvent $event): void
    {
        $user = $event->getUser();

        if ($user->enabled) {
            $this->mailHelper
                ->sendMail(
                    array($user->email => $this->getUserName($user)),
                    'ngmore.user.welcome.subject',
                    $this->configResolver->getParameter('template.user.mail.welcome', 'ngmore'),
                    array(
                        'user' => $user,
                    )
                );

            return;
        }

        $accountKey = $this->ezUserAccountKeyRepository->create($user->id);

        if ($this->configResolver->getParameter('user.require_admin_activation', 'ngmore')) {
            $this->mailHelper
                ->sendMail(
                    array($user->email => $this->getUserName($user)),
                    'ngmore.user.activate.admin_activation_pending.subject',
                    $this->configResolver->getParameter('template.user.mail.activate_admin_activation_pending', 'ngmore'),
                    array(
                        'user' => $user,
                    )
                );

            $adminEmail = $this->configResolver->getParameter('user.mail.admin_email', 'ngmore');
            $adminName = $this->configResolver->getParameter('user.mail.admin_name', 'ngmore');

            if (!empty($adminEmail)) {
                $this->mailHelper
                    ->sendMail(
                        !empty($adminName) ? array($adminEmail => $adminName) : array($adminEmail),
                        'ngmore.user.activate.admin_activation_required.subject',
                        $this->configResolver->getParameter('template.user.mail.activate_admin_activation_required', 'ngmore'),
                        array(
                            'user' => $user,
                        )
                    );
            }

            return;
        }

        $this->mailHelper
            ->sendMail(
                array($user->email => $this->getUserName($user)),
                'ngmore.user.activate.subject',
                $this->configResolver->getParameter('template.user.mail.activate', 'ngmore'),
                array(
                    'user' => $user,
                    'hash' => $accountKey->getHash(),
                )
            );
    }
}
