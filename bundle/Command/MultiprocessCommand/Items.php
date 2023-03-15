<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command\MultiprocessCommand;

use function count;

class Items
{
    private array $items;
    private int $depth;

    public function __construct(array $items, int $depth = 1)
    {
        $this->items = $items;
        $this->depth = $depth;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function getCount(): int
    {
        return count($this->items);
    }
}
