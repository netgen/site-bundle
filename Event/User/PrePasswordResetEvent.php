<?php

namespace Netgen\Bundle\MoreBundle\Event\User;

use Symfony\Component\EventDispatcher\Event;
use eZ\Publish\API\Repository\Values\User\UserUpdateStruct;

class PrePasswordResetEvent extends Event
{
    protected $userUpdateStruct;

    public function __construct( UserUpdateStruct $userUpdateStruct )
    {
        $this->userUpdateStruct = $userUpdateStruct;
    }

    public function getUserUpdateStruct()
    {
        return $this->userUpdateStruct;
    }

    public function setUserUpdateStruct( UserUpdateStruct $userUpdateStruct )
    {
        $this->userUpdateStruct = $userUpdateStruct;
    }
}
