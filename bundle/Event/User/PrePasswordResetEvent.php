<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Event\User;

use eZ\Publish\API\Repository\Values\User\User;
use eZ\Publish\API\Repository\Values\User\UserUpdateStruct;
use Symfony\Component\EventDispatcher\Event;

class PrePasswordResetEvent extends Event
{
    protected User $user;

    protected UserUpdateStruct $userUpdateStruct;

    public function __construct(User $user, UserUpdateStruct $userUpdateStruct)
    {
        $this->user = $user;
        $this->userUpdateStruct = $userUpdateStruct;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getUserUpdateStruct(): UserUpdateStruct
    {
        return $this->userUpdateStruct;
    }

    public function setUserUpdateStruct(UserUpdateStruct $userUpdateStruct): void
    {
        $this->userUpdateStruct = $userUpdateStruct;
    }
}
