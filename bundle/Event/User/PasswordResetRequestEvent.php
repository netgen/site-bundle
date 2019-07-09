<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Event\User;

use eZ\Publish\API\Repository\Values\User\User;
use Symfony\Component\EventDispatcher\Event;

class PasswordResetRequestEvent extends Event
{
    /**
     * @var string
     */
    protected $email;

    /**
     * @var \eZ\Publish\API\Repository\Values\User\User
     */
    protected $user;

    public function __construct(string $email, ?User $user = null)
    {
        $this->email = $email;
        $this->user = $user;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
}
