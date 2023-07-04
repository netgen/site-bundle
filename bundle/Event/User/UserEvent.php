<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Event\User;

use Symfony\Contracts\EventDispatcher\Event;

abstract class UserEvent extends Event
{
    /**
     * @var array<string, mixed>
     */
    private array $parameters = [];

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameter(string $name): mixed
    {
        return $this->parameters[$name] ?? null;
    }

    public function setParameter(string $name, mixed $value): void
    {
        $this->parameters[$name] = $value;
    }
}
