<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Event\User;

use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserUpdateStruct;

final class PrePasswordResetEvent extends UserEvent
{
    public function __construct(
        public private(set) User $user,
        public UserUpdateStruct $userUpdateStruct,
    ) {}
}
