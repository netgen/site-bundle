<?php

namespace Netgen\Bundle\MoreBundle\Event\User;

use Symfony\Component\EventDispatcher\Event;
use eZ\Publish\API\Repository\Values\User\User;

class ActivationRequestEvent extends Event
{
    protected $user;

    protected $email;

    public function __construct( User $user = null, $email )
    {
        $this->user = $user;
        $this->email = $email;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getEmail()
    {
        return $this->email;
    }
}
