<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command\MultiprocessCommand;

use function count;

class ItemList
{
    /**
     * @param mixed[] $items
     */
    public function __construct(private array $items, private int $depth = 1)
    {
    }

    /**
     * @return mixed[]
     */
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
