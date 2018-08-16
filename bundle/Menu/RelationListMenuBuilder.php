<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Netgen\Bundle\MoreBundle\Core\FieldType\RelationList\Value as RelationListValue;
use Netgen\Bundle\MoreBundle\Helper\SiteInfoHelper;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

class RelationListMenuBuilder
{
    /**
     * @var \Knp\Menu\FactoryInterface
     */
    protected $factory;

    /**
     * @var \Netgen\EzPlatformSiteApi\API\LoadService
     */
    protected $loadService;

    /**
     * @var \Netgen\Bundle\MoreBundle\Helper\SiteInfoHelper
     */
    protected $siteInfoHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        FactoryInterface $factory,
        LoadService $loadService,
        SiteInfoHelper $siteInfoHelper,
        LoggerInterface $logger = null
    ) {
        $this->factory = $factory;
        $this->loadService = $loadService;
        $this->siteInfoHelper = $siteInfoHelper;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Creates the KNP menu from provided content and field identifier.
     *
     * @param null|mixed $contentId
     */
    public function createRelationListMenu(string $fieldIdentifier, $contentId = null): ItemInterface
    {
        $content = $contentId !== null ?
            $this->loadService->loadContent($contentId) :
            $this->siteInfoHelper->getSiteInfoContent();

        $menu = $this->factory->createItem('root');

        $menu->setAttribute('location-id', $content->mainLocationId);
        $menu->setExtra('ezlocation', $content->mainLocation);

        if (!$content->hasField($fieldIdentifier)) {
            return $menu;
        }

        $field = $content->getField($fieldIdentifier);
        if (!$field->value instanceof RelationListValue || $field->isEmpty()) {
            return $menu;
        }

        foreach ($field->value->destinationLocationIds as $locationId) {
            if (empty($locationId)) {
                $this->logger->error(sprintf('Empty location ID in RelationList field "%s" for content #%s', $fieldIdentifier, $content->id));

                continue;
            }

            try {
                $location = $this->loadService->loadLocation($locationId);
            } catch (Throwable $t) {
                $this->logger->error($t->getMessage());

                continue;
            }

            $menu->addChild(null, ['ezlocation' => $location]);
        }

        return $menu;
    }
}
