<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command\MultiprocessCommand;

use function count;

final class ItemList
{
    public int $count {
        get => count($this->items);
    }

    /**
     * @param mixed[] $items
     */
    public function __construct(
        public private(set) array $items,
        public private(set) int $depth = 1,
    ) {}
}
