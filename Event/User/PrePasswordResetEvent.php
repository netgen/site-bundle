<?php

namespace Netgen\Bundle\MoreBundle\Event\User;

use Symfony\Component\EventDispatcher\Event;
use eZ\Publish\API\Repository\Values\User\UserUpdateStruct;

class PrePasswordResetEvent extends Event
{
    /**
     * @var \eZ\Publish\API\Repository\Values\User\UserUpdateStruct
     */
    protected $userUpdateStruct;

    /**
     * @param \eZ\Publish\API\Repository\Values\User\UserUpdateStruct $userUpdateStruct
     */
    public function __construct( UserUpdateStruct $userUpdateStruct )
    {
        $this->userUpdateStruct = $userUpdateStruct;
    }

    /**
     * @return \eZ\Publish\API\Repository\Values\User\UserUpdateStruct
     */
    public function getUserUpdateStruct()
    {
        return $this->userUpdateStruct;
    }

    /**
     * @param \eZ\Publish\API\Repository\Values\User\UserUpdateStruct $userUpdateStruct
     */
    public function setUserUpdateStruct( UserUpdateStruct $userUpdateStruct )
    {
        $this->userUpdateStruct = $userUpdateStruct;
    }
}
