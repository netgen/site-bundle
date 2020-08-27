<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Menu;

use eZ\Publish\Core\FieldType\RelationList\Value as RelationListValue;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Netgen\Bundle\EzPlatformSiteApiBundle\NamedObject\Provider;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use function sprintf;

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
     * @var \Netgen\Bundle\EzPlatformSiteApiBundle\NamedObject\Provider
     */
    protected $namedObjectProvider;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        FactoryInterface $factory,
        LoadService $loadService,
        Provider $namedObjectProvider,
        ?LoggerInterface $logger = null
    ) {
        $this->factory = $factory;
        $this->loadService = $loadService;
        $this->namedObjectProvider = $namedObjectProvider;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Creates the KNP menu from provided content and field identifier.
     */
    public function createRelationListMenu(string $fieldIdentifier, ?int $contentId = null): ItemInterface
    {
        $content = $contentId !== null ?
            $this->loadService->loadContent($contentId) :
            $this->namedObjectProvider->getLocation('site_info')->content;

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

        foreach ($field->value->destinationContentIds as $destinationContentId) {
            if (empty($destinationContentId)) {
                $this->logger->error(sprintf('Empty content ID in RelationList field "%s" for content #%s', $fieldIdentifier, $content->id));

                continue;
            }

            try {
                $destinationContent = $this->loadService->loadContent($destinationContentId);
            } catch (Throwable $t) {
                $this->logger->error($t->getMessage());

                continue;
            }

            $menu->addChild($this->factory->createItem('', ['ezlocation' => $destinationContent->mainLocation]));
        }

        return $menu;
    }
}
