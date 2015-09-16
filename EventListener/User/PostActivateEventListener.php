<?php

namespace Netgen\Bundle\MoreBundle\EventListener\User;

use Netgen\Bundle\MoreBundle\EventListener\UserEventListener;
use Netgen\Bundle\MoreBundle\Event\User\PostActivateEvent;

class PostActivateEventListener extends UserEventListener
{

    /**
     * Listens to the event triggered after the user activation has been finished.
     * Event contains information about activated user.
     *
     * @param \Netgen\Bundle\MoreBundle\Event\User\PostActivateEvent $event
     */
    public function onPostActivate( PostActivateEvent $event )
    {
        $user = $event->getUser();

        $this->ezUserAccountKeyRepository->removeByUserId( $user->id );
        $this->ngUserSettingRepository->activateUser( $user->id );

        $this->mailHelper
            ->sendMail(
                $user->email,
                $this->configResolver->getParameter( 'template.user.mail.welcome', 'ngmore' ),
                'ngmore.user.welcome.subject',
                array(
                    'user' => $user
                )
            );
    }
}
