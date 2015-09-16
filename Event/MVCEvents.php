<?php

namespace Netgen\Bundle\MoreBundle\Event;

final class MVCEvents
{
    /**
     * The ACTIVATION_REQUEST event occurs after the user activation process has been started.
     *
     * The event listener method receives a \Netgen\Bundle\MoreBundle\Event\User\ActivationRequestEvent
     */
    const USER_ACTIVATION_REQUEST = 'ngmore.events.user.activation_request';

    /**
     * The USER_PRE_ACTIVATE event occurs just before the user has been activated.
     * It is possible to manipulate the userUpdateStruct in the listener of this event.
     *
     * The event listener method receives a \Netgen\Bundle\MoreBundle\Event\User\PreActivateEvent
     */
    const USER_PRE_ACTIVATE = 'ngmore.events.user.pre_activate';

    /**
     * The USER_POST_ACTIVATE event occurs just after the user has been activated.
     *
     * The event listener method receives a \Netgen\Bundle\MoreBundle\Event\User\PostActivateEvent
     */
    const USER_POST_ACTIVATE = 'ngmore.events.user.post_activate';

    /**
     * The USER_PRE_REGISTER event occurs just before the user has been registered.
     * It gives control over the userCreateStruct before actually creating the user.
     *
     * The event listener method receives a \Netgen\Bundle\MoreBundle\Event\User\PreRegisterEvent
     */
    const USER_PRE_REGISTER = 'ngmore.events.user.pre_register';

    /**
     * The USER_POST_REGISTER event occurs just after the user has been registered.
     *
     * The event listener method receives a \Netgen\Bundle\MoreBundle\Event\User\PostRegisterEvent
     */
    const USER_POST_REGISTER = 'ngmore.events.user.post_register';

    /**
     * The USER_PASSWORD_RESET_REQUEST event occurs after the password reset procedure has been started.
     *
     * The event listener method receives a \Netgen\Bundle\MoreBundle\Event\User\PasswordResetRequestEvent
     */
    const USER_PASSWORD_RESET_REQUEST = 'ngmore.events.user.password_reset_request';

    /**
     * The USER_PRE_PASSWORD_RESET event occurs just before the password on the user has been changed.
     * It enables manipulating the userUpdateStruct which is used to change the password.
     *
     * The event listener method receives a \Netgen\Bundle\MoreBundle\Event\User\PrePasswordResetEvent
     */
    const USER_PRE_PASSWORD_RESET = 'ngmore.events.user.pre_password_reset';

    /**
     * The USER_POST_PASSWORD_RESET event occurs just after the password on the user has been changed.
     *
     * The event listener method receives a \Netgen\Bundle\MoreBundle\Event\User\PostPasswordResetEvent
     */
    const USER_POST_PASSWORD_RESET = 'ngmore.events.user.post_password_reset';
}
