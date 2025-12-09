<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Event\Menu;

use Knp\Menu\ItemInterface;
use Netgen\IbexaSiteApi\API\Values\Location;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is triggered when a menu item is build using the location menu factory.
 */
final class LocationMenuItemEvent extends Event
{
    public function __construct(
        /**
         * Returns the item which was built.
         */
        public private(set) ItemInterface $item,
        /**
         * Returns the Ibexa location for which the menu item was built.
         */
        public private(set) Location $location,
    ) {}
}
