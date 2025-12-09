<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Event\User;

use Ibexa\Contracts\Core\Repository\Values\User\UserCreateStruct;

final class PreRegisterEvent extends UserEvent
{
    public function __construct(
        public UserCreateStruct $userCreateStruct,
    ) {}
}
