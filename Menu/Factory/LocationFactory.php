<?php

namespace Netgen\Bundle\MoreBundle\Menu\Factory;

use Knp\Menu\FactoryInterface;
use Knp\Menu\MenuItem;
use Netgen\Bundle\MoreBundle\Event\Menu\LocationMenuItemEvent;
use Netgen\Bundle\MoreBundle\Event\MVCEvents;
use Netgen\Bundle\MoreBundle\Menu\Factory\LocationFactory\ExtensionInterface;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class LocationFactory implements FactoryInterface
{
    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var \Netgen\Bundle\MoreBundle\Menu\Factory\LocationFactory\ExtensionInterface
     */
    protected $fallbackExtension;

    /**
     * @var \Netgen\Bundle\MoreBundle\Menu\Factory\LocationFactory\ExtensionInterface[]
     */
    protected $extensions = array();

    /**
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
     * @param \Netgen\Bundle\MoreBundle\Menu\Factory\LocationFactory\ExtensionInterface $fallbackExtension
     * @param \Netgen\Bundle\MoreBundle\Menu\Factory\LocationFactory\ExtensionInterface[] $extensions
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ExtensionInterface $fallbackExtension,
        array $extensions = array()
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->fallbackExtension = $fallbackExtension;
        $this->extensions = $extensions;
    }

    public function createItem($name, array $options = array())
    {
        $menuItem = new MenuItem($name, $this);

        if (!isset($options['ezlocation']) || !$options['ezlocation'] instanceof Location) {
            return $menuItem;
        }

        $menuItem
            ->setExtra('translation_domain', false)
            ->setExtra('ezlocation', $options['ezlocation']);

        $extension = $this->getExtension($options['ezlocation']);
        $extension->buildItem($menuItem, $options['ezlocation']);

        $event = new LocationMenuItemEvent($menuItem, $menuItem->getExtra('ezlocation'));
        $this->eventDispatcher->dispatch(MVCEvents::MENU_LOCATION_ITEM, $event);

        return $menuItem;
    }

    /**
     * Returns the first extension that matches the provided location.
     *
     * If none match, fallback extension is returned.
     *
     * @param \Netgen\EzPlatformSiteApi\API\Values\Location $location
     *
     * @return \Netgen\Bundle\MoreBundle\Menu\Factory\LocationFactory\ExtensionInterface
     */
    private function getExtension(Location $location)
    {
        foreach ($this->extensions as $extension) {
            if ($extension->matches($location)) {
                return $extension;
            }
        }

        return $this->fallbackExtension;
    }
}
