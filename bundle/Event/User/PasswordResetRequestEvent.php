<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Event\User;

use Ibexa\Contracts\Core\Repository\Values\User\User;

final class PasswordResetRequestEvent extends UserEvent
{
    public function __construct(private string $email, private ?User $user = null) {}

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
}
