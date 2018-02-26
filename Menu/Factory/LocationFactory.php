<?php

namespace Netgen\Bundle\MoreBundle\Menu\Factory;

use Knp\Menu\FactoryInterface;
use Knp\Menu\MenuItem;
use Netgen\Bundle\MoreBundle\Menu\Factory\LocationFactory\ExtensionInterface;
use Netgen\EzPlatformSiteApi\API\Values\Location;

class LocationFactory implements FactoryInterface
{
    /**
     * @var \Netgen\Bundle\MoreBundle\Menu\Factory\LocationFactory\ExtensionInterface
     */
    protected $fallbackExtension;

    /**
     * @var \Netgen\Bundle\MoreBundle\Menu\Factory\LocationFactory\ExtensionInterface[]
     */
    protected $extensions = array();

    /**
     * @param \Netgen\Bundle\MoreBundle\Menu\Factory\LocationFactory\ExtensionInterface $fallbackExtension
     * @param \Netgen\Bundle\MoreBundle\Menu\Factory\LocationFactory\ExtensionInterface[] $extensions
     */
    public function __construct(ExtensionInterface $fallbackExtension, array $extensions = array())
    {
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
