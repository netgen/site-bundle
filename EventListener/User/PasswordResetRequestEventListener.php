<?php

namespace Netgen\Bundle\MoreBundle\EventListener\User;

use Netgen\Bundle\MoreBundle\EventListener\UserEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Netgen\Bundle\MoreBundle\Event\MVCEvents;
use Netgen\Bundle\MoreBundle\Event\User\PasswordResetRequestEvent;
use eZ\Publish\API\Repository\Values\User\User;

class PasswordResetRequestEventListener extends UserEventListener implements EventSubscriberInterface
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            MVCEvents::USER_PASSWORD_RESET_REQUEST => 'onPasswordResetRequest',
        );
    }

    /**
     * Listens for the start of forgot password procedure.
     * Event contains information about the submitted email and the user, if found.
     *
     * @param \Netgen\Bundle\MoreBundle\Event\User\PasswordResetRequestEvent $event
     */
    public function onPasswordResetRequest(PasswordResetRequestEvent $event)
    {
        $user = $event->getUser();
        $email = $event->getEmail();

        if (!$user instanceof User) {
            $this->mailHelper
                ->sendMail(
                    $email,
                    'ngmore.user.forgot_password.not_registered.subject',
                    $this->configResolver->getParameter('template.user.mail.forgot_password_not_registered', 'ngmore')
                );

            return;
        }

        if (!$user->enabled) {
            if ($this->ngUserSettingRepository->isUserActivated($user->id)) {
                $this->mailHelper
                    ->sendMail(
                        array($user->email => $this->getUserName($user)),
                        'ngmore.user.forgot_password.disabled.subject',
                        $this->configResolver->getParameter('template.user.mail.forgot_password_disabled', 'ngmore'),
                        array(
                            'user' => $user,
                        )
                    );

                return;
            }

            $this->mailHelper
                ->sendMail(
                    array($user->email => $this->getUserName($user)),
                    'ngmore.user.forgot_password.not_active.subject',
                    $this->configResolver->getParameter('template.user.mail.forgot_password_not_active', 'ngmore'),
                    array(
                        'user' => $user,
                    )
                );

            return;
        }

        $accountKey = $this->ezUserAccountKeyRepository->create($user->id);

        $this->mailHelper
            ->sendMail(
                array($user->email => $this->getUserName($user)),
                'ngmore.user.forgot_password.subject',
                $this->configResolver->getParameter('template.user.mail.forgot_password', 'ngmore'),
                array(
                    'user' => $user,
                    'hash' => $accountKey->getHash(),
                )
            );
    }
}
