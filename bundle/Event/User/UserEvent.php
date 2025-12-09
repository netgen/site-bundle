<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Event\User;

use Symfony\Contracts\EventDispatcher\Event;

abstract class UserEvent extends Event
{
    /**
     * @var array<string, mixed>
     */
    public private(set) array $parameters = [];

    public function getParameter(string $name): mixed
    {
        return $this->parameters[$name] ?? null;
    }

    public function setParameter(string $name, mixed $value): void
    {
        $this->parameters[$name] = $value;
    }
}
