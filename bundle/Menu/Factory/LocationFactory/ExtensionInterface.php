<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Menu\Factory\LocationFactory;

use Knp\Menu\ItemInterface;
use Netgen\EzPlatformSiteApi\API\Values\Location;

interface ExtensionInterface
{
    /**
     * Returns if the extension can be used to configure the item based on provided location.
     *
     * @param \Netgen\EzPlatformSiteApi\API\Values\Location $location
     *
     * @return bool
     */
    public function matches(Location $location): bool;

    /**
     * Configures the item with the passed options.
     *
     * @param \Knp\Menu\ItemInterface $item
     * @param \Netgen\EzPlatformSiteApi\API\Values\Location $location
     */
    public function buildItem(ItemInterface $item, Location $location): void;
}
