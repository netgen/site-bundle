<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Menu\Factory\LocationFactory;

use Knp\Menu\ItemInterface;
use Netgen\IbexaSiteApi\API\Values\Location;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class FallbackExtension implements ExtensionInterface
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function matches(Location $location): bool
    {
        return true;
    }

    public function buildItem(ItemInterface $item, Location $location): void
    {
        $item
            ->setUri($this->urlGenerator->generate('', [RouteObjectInterface::ROUTE_OBJECT => $location]))
            ->setAttribute('id', 'menu-item-' . $item->getExtra('menu_name') . '-location-id-' . $location->id);
    }
}
