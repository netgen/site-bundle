<?php

namespace Netgen\Bundle\MoreBundle\EventListener\User;

use Netgen\Bundle\MoreBundle\EventListener\UserEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Netgen\Bundle\MoreBundle\Event\MVCEvents;
use Netgen\Bundle\MoreBundle\Event\User\PostRegisterEvent;

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
            MVCEvents::USER_POST_REGISTER => 'onUserRegistered'
        );
    }

    /**
     * Listens to the event triggered after the user has been registered.
     * The event contains information about registered user.
     *
     * @param \Netgen\Bundle\MoreBundle\Event\User\PostRegisterEvent $event
     */
    public function onUserRegistered( PostRegisterEvent $event )
    {
        $user = $event->getUser();

        if ( (bool)$this->configResolver->getParameter( 'user.auto_enable', 'ngmore' ) )
        {
            $this->mailHelper
                ->sendMail(
                    array( $user->email => $this->translationHelper->getTranslatedContentName( $user ) ),
                    'ngmore.user.welcome.subject',
                    $this->configResolver->getParameter( 'template.user.mail.welcome', 'ngmore' ),
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
