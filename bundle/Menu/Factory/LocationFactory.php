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

class LocationFactory implements FactoryInterface
{
    protected EventDispatcherInterface $eventDispatcher;

    protected ExtensionInterface $fallbackExtension;

    /**
     * @var \Netgen\Bundle\SiteBundle\Menu\Factory\LocationFactory\ExtensionInterface[]
     */
    protected array $extensions = [];

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ExtensionInterface $fallbackExtension,
        array $extensions = []
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->fallbackExtension = $fallbackExtension;
        $this->extensions = $extensions;
    }

    public function createItem($name, array $options = []): ItemInterface
    {
        $menuItem = (new MenuItem($name, $this))->setExtra('translation_domain', false);

        if (!isset($options['ibexa_location']) || !$options['ibexa_location'] instanceof Location) {
            return $menuItem;
        }

        $locationId = $options['ibexa_location']->id;

        $menuItem
            ->setLabel($options['ibexa_location']->content->name)
            ->setExtra('ibexa_location', $options['ibexa_location'])
            // Used to preserve the reference to the original menu item location
            // (e.g. in case of menu item or shortcut where ibexa_location will be overwritten
            // by the location of related content)
            ->setExtra('menu_item_location', $options['ibexa_location']);

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
    protected function getExtension(Location $location): ExtensionInterface
    {
        foreach ($this->extensions as $extension) {
            if ($extension->matches($location)) {
                return $extension;
            }
        }

        return $this->fallbackExtension;
    }
}
