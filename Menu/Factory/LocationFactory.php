<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Menu\Factory;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuItem;
use Netgen\Bundle\MoreBundle\Event\Menu\LocationMenuItemEvent;
use Netgen\Bundle\MoreBundle\Event\NetgenMoreEvents;
use Netgen\Bundle\MoreBundle\Menu\Factory\LocationFactory\ExtensionInterface;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class LocationFactory implements FactoryInterface
{
    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var \Netgen\Bundle\MoreBundle\Menu\Factory\LocationFactory\ExtensionInterface
     */
    protected $fallbackExtension;

    /**
     * @var \Netgen\Bundle\MoreBundle\Menu\Factory\LocationFactory\ExtensionInterface[]
     */
    protected $extensions = [];

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

        $menuItem
            ->setLabel($options['ezlocation']->content->name)
            ->setExtra('ezlocation', $options['ezlocation']);

        $extension = $this->getExtension($options['ezlocation']);
        $extension->buildItem($menuItem, $options['ezlocation']);

        $menuItem->setName(md5($menuItem->getUri() ?? ''));

        $event = new LocationMenuItemEvent($menuItem, $menuItem->getExtra('ezlocation'));
        $this->eventDispatcher->dispatch(NetgenMoreEvents::MENU_LOCATION_ITEM, $event);

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
