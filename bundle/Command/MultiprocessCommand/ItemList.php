<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command\MultiprocessCommand;

use function count;

class ItemList
{
    /**
     * @var mixed[]
     */
    private array $items;

    private int $depth;

    /**
     * @param mixed[] $items
     */
    public function __construct(array $items, int $depth = 1)
    {
        $this->items = $items;
        $this->depth = $depth;
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
