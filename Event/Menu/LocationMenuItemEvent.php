<?php

namespace Netgen\Bundle\MoreBundle\Event\Menu;

use Knp\Menu\ItemInterface;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use Symfony\Component\EventDispatcher\Event;

/**
 * This event is triggered when a menu item is build using the location menu factory.
 */
class LocationMenuItemEvent extends Event
{
    /**
     * @var \Knp\Menu\ItemInterface
     */
    protected $item;

    /**
     * @var \Netgen\EzPlatformSiteApi\API\Values\Location
     */
    protected $location;

    public function __construct(ItemInterface $item, Location $location)
    {
        $this->item = $item;
        $this->location = $location;
    }

    /**
     * Returns the item which was built.
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * Returns the eZ Publish location for which the menu item was built.
     *
     * @return \Netgen\EzPlatformSiteApi\API\Values\Location
     */
    public function getLocation()
    {
        return $this->location;
    }
}
