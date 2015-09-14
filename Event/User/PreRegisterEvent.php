<?php

namespace Netgen\Bundle\MoreBundle\Event\User;

use Symfony\Component\EventDispatcher\Event;
use eZ\Publish\API\Repository\Values\User\UserCreateStruct;

class PreRegisterEvent extends Event
{
    protected $userCreateStruct;

    public function __construct( UserCreateStruct $userCreateStruct )
    {
        $this->userCreateStruct = $userCreateStruct;
    }

    public function setUserCreateStruct( UserCreateStruct $userCreateStruct )
    {
        $this->userCreateStruct = $userCreateStruct;
    }

    public function getUserCreateStruct()
    {
        return $this->userCreateStruct;
    }
}
