<?php

namespace Netgen\Bundle\MoreBundle\Event\User;

use Symfony\Component\EventDispatcher\Event;
use eZ\Publish\API\Repository\Values\User\User;

class PostRegisterEvent extends Event
{
    /**
     * @var \eZ\Publish\API\Repository\Values\User\User
     */
    protected $user;

    /**
     * @var bool
     */
    protected $autoEnabled;

    /**
     * @param \eZ\Publish\API\Repository\Values\User\User $user
     * @param bool $autoEnabled
     */
    public function __construct( User $user, $autoEnabled )
    {
        $this->user = $user;
        $this->autoEnabled = $autoEnabled;
    }

    /**
     * @return \eZ\Publish\API\Repository\Values\User\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return bool
     */
    public function isAutoEnabled()
    {
        return $this->autoEnabled;
    }
}
