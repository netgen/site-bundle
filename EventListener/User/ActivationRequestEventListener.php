<?php

namespace Netgen\Bundle\MoreBundle\EventListener\User;

use Netgen\Bundle\MoreBundle\EventListener\UserEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Netgen\Bundle\MoreBundle\Event\MVCEvents;
use Netgen\Bundle\MoreBundle\Event\User\ActivationRequestEvent;
use eZ\Publish\API\Repository\Values\User\User;

class ActivationRequestEventListener extends UserEventListener implements EventSubscriberInterface
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            MVCEvents::USER_ACTIVATION_REQUEST => 'onActivationRequest'
        );
    }

    /**
     * Listens for the start of the activation process.
     * Event contains information about the submitted email and the user, if found.
     *
     * @param \Netgen\Bundle\MoreBundle\Event\User\ActivationRequestEvent $event
     */
    public function onActivationRequest( ActivationRequestEvent $event )
    {
        $user = $event->getUser();
        $email = $event->getEmail();

        if ( !$user instanceof User )
        {
            $this->mailHelper->sendMail(
                $email,
                'ngmore.user.activate.not_registered.subject',
                $this->configResolver->getParameter( 'template.user.mail.activate_not_registered', 'ngmore' )
            );

            return;
        }

        if ( $user->enabled )
        {
            $this->mailHelper->sendMail(
                array( $user->email => $this->translationHelper->getTranslatedContentName( $user ) ),
                'ngmore.user.activate.already_active.subject',
                $this->configResolver->getParameter( 'template.user.mail.activate_already_active', 'ngmore' ),
                array(
                    'user' => $user
                )
            );

            return;
        }

        if ( $this->ngUserSettingRepository->isUserActivated( $user->id ) )
        {
            $this->mailHelper->sendMail(
                array( $user->email => $this->translationHelper->getTranslatedContentName( $user ) ),
                'ngmore.user.activate.disabled.subject',
                $this->configResolver->getParameter( 'template.user.mail.activate_disabled', 'ngmore' ),
                array(
                    'user' => $user
                )
            );

            return;
        }

        $accountKey = $this->ezUserAccountKeyRepository->create( $user->id );

        $this->mailHelper
            ->sendMail(
                array( $user->email => $this->translationHelper->getTranslatedContentName( $user ) ),
                'ngmore.user.activate.subject',
                $this->configResolver->getParameter( 'template.user.mail.activate', 'ngmore' ),
                array(
                    'user' => $user,
                    'hash' => $accountKey->getHash()
                )
            );
    }
}
