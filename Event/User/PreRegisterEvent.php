<?php

namespace Netgen\Bundle\MoreBundle\Event\User;

use Symfony\Component\EventDispatcher\Event;
use eZ\Publish\API\Repository\Values\User\UserCreateStruct;

class PreRegisterEvent extends Event
{
    /**
     * @var \eZ\Publish\API\Repository\Values\User\UserCreateStruct
     */
    protected $userCreateStruct;

    /**
     * @param \eZ\Publish\API\Repository\Values\User\UserCreateStruct $userCreateStruct
     */
    public function __construct( UserCreateStruct $userCreateStruct )
    {
        $this->userCreateStruct = $userCreateStruct;
    }

    /**
     * @param \eZ\Publish\API\Repository\Values\User\UserCreateStruct $userCreateStruct
     */
    public function setUserCreateStruct( UserCreateStruct $userCreateStruct )
    {
        $this->userCreateStruct = $userCreateStruct;
    }

    /**
     * @return \eZ\Publish\API\Repository\Values\User\UserCreateStruct
     */
    public function getUserCreateStruct()
    {
        return $this->userCreateStruct;
    }
}
