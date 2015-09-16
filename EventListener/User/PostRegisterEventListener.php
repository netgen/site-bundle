<?php

namespace Netgen\Bundle\MoreBundle\EventListener\User;

use Netgen\Bundle\MoreBundle\EventListener\UserEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Netgen\Bundle\MoreBundle\Event\MVCEvents;
use Netgen\Bundle\MoreBundle\Event\User\PostRegisterEvent;

class PostRegisterEventListener extends UserEventListener implements EventSubscriberInterface
{
    /**
     * Listens to the event triggered after the user has been registered.
     * The event contains information about registered user.
     *
     * @param \Netgen\Bundle\MoreBundle\Event\User\PostRegisterEvent $event
     */
    public function onUserRegistered( PostRegisterEvent $event )
    {
        if ( (bool)$this->configResolver->getParameter( 'user.auto_enable', 'ngmore' ) )
        {
            $this->mailHelper
                ->sendMail(
                    $event->getUser()->email,
                    $this->configResolver->getParameter( 'template.user.mail.welcome', 'ngmore' ),
                    'ngmore.user.welcome.subject',
                    array(
                        'user' => $event->getUser()
                    )
                );

            return;
        }

        $accountKey = $this->ezUserAccountKeyRepository->create( $event->getUser()->id );

        $this->mailHelper
            ->sendMail(
                $event->getUser()->email,
                $this->configResolver->getParameter( 'template.user.mail.activate', 'ngmore' ),
                'ngmore.user.activate.subject',
                array(
                    'user' => $event->getUser(),
                    'hash' => $accountKey->getHash()
                )
            );
    }

    public static function getSubscribedEvents()
    {
        return array(
            MVCEvents::USER_POST_REGISTER => 'onUserRegistered'
        );
    }
}
