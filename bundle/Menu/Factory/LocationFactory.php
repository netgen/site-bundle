<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Menu\Factory;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuItem;
use Netgen\Bundle\SiteBundle\Event\Menu\LocationMenuItemEvent;
use Netgen\Bundle\SiteBundle\Event\SiteEvents;
use Netgen\Bundle\SiteBundle\Menu\Factory\LocationFactory\ExtensionInterface;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

        if (!isset($options['ezlocation']) || !$options['ezlocation'] instanceof Location) {
            return $menuItem;
        }

        $locationId = $options['ezlocation']->id;

        $menuItem
            ->setLabel($options['ezlocation']->content->name)
            ->setExtra('ezlocation', $options['ezlocation'])
            ->setExtra('menu_name', $options['menu_name'])
            // Used to preserve the reference to the original menu item location
            // (e.g. in case of menu item or shortcut where ezlocation will be overwritten
            // by the location of related content)
            ->setExtra('menu_item_location', $options['ezlocation']);

        $extension = $this->getExtension($options['ezlocation']);
        $extension->buildItem($menuItem, $options['ezlocation']);

        $menuItem->setName(md5(($menuItem->getUri() ?? '') . '-' . $locationId));

        $event = new LocationMenuItemEvent($menuItem, $menuItem->getExtra('ezlocation'));
        $this->eventDispatcher->dispatch(SiteEvents::MENU_LOCATION_ITEM, $event);

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
