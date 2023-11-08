<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Event\User;

use Ibexa\Contracts\Core\Repository\Values\User\User;

final class PostActivateEvent extends UserEvent
{
    public function __construct(private User $user) {}

    public function getUser(): User
    {
        return $this->user;
    }
}
