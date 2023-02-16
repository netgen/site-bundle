<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Menu\Factory;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuItem;
use Netgen\Bundle\SiteBundle\Event\Menu\LocationMenuItemEvent;
use Netgen\Bundle\SiteBundle\Event\SiteEvents;
use Netgen\Bundle\SiteBundle\Menu\Factory\LocationFactory\ExtensionInterface;
use Netgen\IbexaSiteApi\API\Values\Location;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

use function md5;

final class LocationFactory implements FactoryInterface
{
    /**
     * @param \Netgen\Bundle\SiteBundle\Menu\Factory\LocationFactory\ExtensionInterface[] $extensions
     */
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private ExtensionInterface $fallbackExtension,
        private array $extensions = [],
    ) {
    }

    public function createItem(string $name, array $options = []): ItemInterface
    {
        $menuItem = (new MenuItem($name, $this))->setExtra('translation_domain', false);

        if (!isset($options['ibexa_location']) || !$options['ibexa_location'] instanceof Location) {
            return $menuItem;
        }

        $locationId = $options['ibexa_location']->id;

        $menuItem
            ->setLabel($options['ibexa_location']->content->name)
            ->setExtra('ibexa_location', $options['ibexa_location'])
            ->setExtra('menu_name', $options['menu_name'])
            // Used to preserve the reference to the original menu item location
            // (e.g. in case of menu item or shortcut where ibexa_location will be overwritten
            // by the location of related content)
            ->setExtra('menu_item_location', $options['ibexa_location'])
            ->setExtra('index', $options['index']);

        $extension = $this->getExtension($options['ibexa_location']);
        $extension->buildItem($menuItem, $options['ibexa_location']);

        $menuItem->setName(md5(($menuItem->getUri() ?? '') . '-' . $locationId));

        $event = new LocationMenuItemEvent($menuItem, $menuItem->getExtra('ibexa_location'));
        $this->eventDispatcher->dispatch($event, SiteEvents::MENU_LOCATION_ITEM);

        return $menuItem;
    }

    /**
     * Returns the first extension that matches the provided location.
     *
     * If none match, fallback extension is returned.
     */
    private function getExtension(Location $location): ExtensionInterface
    {
        foreach ($this->extensions as $extension) {
            if ($extension->matches($location)) {
                return $extension;
            }
        }

        return $this->fallbackExtension;
    }
}
