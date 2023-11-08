<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Event\User;

use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserUpdateStruct;

final class PreActivateEvent extends UserEvent
{
    public function __construct(private User $user, private UserUpdateStruct $userUpdateStruct) {}

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
