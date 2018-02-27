<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Event\User;

use eZ\Publish\API\Repository\Values\User\User;
use eZ\Publish\API\Repository\Values\User\UserUpdateStruct;
use Symfony\Component\EventDispatcher\Event;

class PreActivateEvent extends Event
{
    /**
     * @var \eZ\Publish\API\Repository\Values\User\User
     */
    protected $user;

    /**
     * @var \eZ\Publish\API\Repository\Values\User\UserUpdateStruct;
     */
    protected $userUpdateStruct;

    /**
     * @param \eZ\Publish\API\Repository\Values\User\User $user
     * @param \eZ\Publish\API\Repository\Values\User\UserUpdateStruct $userUpdateStruct
     */
    public function __construct(User $user, UserUpdateStruct $userUpdateStruct)
    {
        $this->user = $user;
        $this->userUpdateStruct = $userUpdateStruct;
    }

    /**
     * @return \eZ\Publish\API\Repository\Values\User\User
     */
    public function getUser()
    {
        return $this->user;
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
    public function setUserUpdateStruct(UserUpdateStruct $userUpdateStruct)
    {
        $this->userUpdateStruct = $userUpdateStruct;
    }
}
