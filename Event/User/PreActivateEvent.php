<?php

namespace Netgen\Bundle\MoreBundle\Event\User;

use Symfony\Component\EventDispatcher\Event;
use eZ\Publish\API\Repository\Values\User\User;

class PreActivateEvent extends Event
{
    protected $user;

    public function __construct( User $user )
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser( User $user )
    {
        $this->user = $user;
    }
}
