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
     * Listens for the start of forgotpassword procedure.
     * Event contains information about the submitted email and the user, if found.
     *
     * @param \Netgen\Bundle\MoreBundle\Event\User\PasswordResetRequestEvent $event
     */
    public function onPasswordResetRequest( PasswordResetRequestEvent $event )
    {
        $user = $event->getUser();
        $email = $event->getEmail();

        if ( !$user instanceof User )
        {
            $this->mailHelper
                ->sendMail(
                    $email,
                    $this->configResolver->getParameter( 'template.user.mail.forgot_password_not_registered', 'ngmore' ),
                    'ngmore.user.forgot_password.not_registered.subject'
                );

            return;
        }

        if ( !$user->enabled )
        {
            if ( $this->ngUserSettingRepository->isUserActivated( $user->id ) )
            {
                $this->mailHelper
                    ->sendMail(
                        $email,
                        $this->configResolver->getParameter( 'template.user.mail.forgot_password_disabled', 'ngmore' ),
                        'ngmore.user.forgot_password.disabled.subject',
                        array(
                            'user' => $user,
                        )
                    );
            }
            else
            {
                $this->mailHelper
                    ->sendMail(
                        $email,
                        $this->configResolver->getParameter( 'template.user.mail.forgot_password_not_active', 'ngmore' ),
                        'ngmore.user.forgot_password.not_active.subject',
                        array(
                            'user' => $user,
                        )
                    );
            }

            return;
        }

        $accountKey = $this->ezUserAccountKeyRepository->create( $user->id );

        $this->mailHelper
            ->sendMail(
                $user->email,
                $this->configResolver->getParameter( 'template.user.mail.forgot_password', 'ngmore' ),
                'ngmore.user.forgot_password.subject',
                array(
                    'user' => $user,
                    'hash' => $accountKey->getHash()
                )
            );
    }

    public static function getSubscribedEvents()
    {
        return array(
            MVCEvents::USER_PASSWORD_RESET_REQUEST => 'onPasswordResetRequest'
        );
    }
}
