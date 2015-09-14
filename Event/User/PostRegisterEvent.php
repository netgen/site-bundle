<?php

namespace Netgen\Bundle\MoreBundle\Event\User;

use Symfony\Component\EventDispatcher\Event;
use eZ\Publish\API\Repository\Values\User\User;

class PostRegisterEvent extends Event
{
    protected $user;

    protected $autoEnabled;

    public function __construct( User $user, $autoEnabled )
    {
        $this->user = $user;
        $this->autoEnabled = $autoEnabled;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function isAutoEnabled()
    {
        return $this->autoEnabled;
    }
}
