<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Event;

final class SiteEvents
{
    /**
     * The ACTIVATION_REQUEST event occurs after the user activation process has been started.
     *
     * The event listener method receives a \Netgen\Bundle\SiteBundle\Event\User\ActivationRequestEvent
     */
    public const USER_ACTIVATION_REQUEST = 'ngsite.events.user.activation_request';

    /**
     * The USER_PRE_ACTIVATE event occurs just before the user has been activated.
     * It is possible to manipulate the user update struct in the listener of this event.
     *
     * The event listener method receives a \Netgen\Bundle\SiteBundle\Event\User\PreActivateEvent
     */
    public const USER_PRE_ACTIVATE = 'ngsite.events.user.pre_activate';

    /**
     * The USER_POST_ACTIVATE event occurs just after the user has been activated.
     *
     * The event listener method receives a \Netgen\Bundle\SiteBundle\Event\User\PostActivateEvent
     */
    public const USER_POST_ACTIVATE = 'ngsite.events.user.post_activate';

    /**
     * The USER_PRE_REGISTER event occurs just before the user has been registered.
     * It is possible to manipulate the user create struct before actually creating the user.
     *
     * The event listener method receives a \Netgen\Bundle\SiteBundle\Event\User\PreRegisterEvent
     */
    public const USER_PRE_REGISTER = 'ngsite.events.user.pre_register';

    /**
     * The USER_POST_REGISTER event occurs just after the user has been registered.
     *
     * The event listener method receives a \Netgen\Bundle\SiteBundle\Event\User\PostRegisterEvent
     */
    public const USER_POST_REGISTER = 'ngsite.events.user.post_register';

    /**
     * The USER_PASSWORD_RESET_REQUEST event occurs after the password reset procedure has been started.
     *
     * The event listener method receives a \Netgen\Bundle\SiteBundle\Event\User\PasswordResetRequestEvent
     */
    public const USER_PASSWORD_RESET_REQUEST = 'ngsite.events.user.password_reset_request';

    /**
     * The USER_PRE_PASSWORD_RESET event occurs just before the password on the user has been changed.
     * It is possible to manipulate the user update struct in the listener of this event.
     *
     * The event listener method receives a \Netgen\Bundle\SiteBundle\Event\User\PrePasswordResetEvent
     */
    public const USER_PRE_PASSWORD_RESET = 'ngsite.events.user.pre_password_reset';

    /**
     * The USER_POST_PASSWORD_RESET event occurs just after the password on the user has been changed.
     *
     * The event listener method receives a \Netgen\Bundle\SiteBundle\Event\User\PostPasswordResetEvent
     */
    public const USER_POST_PASSWORD_RESET = 'ngsite.events.user.post_password_reset';

    /**
     * The CONTENT_DOWNLOAD event occurs after download content.
     *
     * The event listener method receives a \Netgen\Bundle\SiteBundle\Event\Content\DownloadEvent
     */
    public const CONTENT_DOWNLOAD = 'ngsite.events.content.download';

    /**
     * The MENU_LOCATION_ITEM event occurs when a menu item is build using location menu factory.
     *
     * The event listener method receives a \Netgen\Bundle\SiteBundle\Event\Menu\LocationMenuItemEvent
     */
    public const MENU_LOCATION_ITEM = 'ngsite.events.menu.location_item';
}
