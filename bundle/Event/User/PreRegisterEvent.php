<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Event\User;

use eZ\Publish\API\Repository\Values\User\UserCreateStruct;
use Symfony\Component\EventDispatcher\Event;

class PreRegisterEvent extends Event
{
    protected UserCreateStruct $userCreateStruct;

    public function __construct(UserCreateStruct $userCreateStruct)
    {
        $this->userCreateStruct = $userCreateStruct;
    }

    public function setUserCreateStruct(UserCreateStruct $userCreateStruct): void
    {
        $this->userCreateStruct = $userCreateStruct;
    }

    public function getUserCreateStruct(): UserCreateStruct
    {
        return $this->userCreateStruct;
    }
}
