<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Event\User;

use Ibexa\Contracts\Core\Repository\Values\User\User;

final class PasswordResetRequestEvent extends UserEvent
{
    public function __construct(
        public private(set) string $email,
        public private(set) ?User $user = null,
    ) {}
}
