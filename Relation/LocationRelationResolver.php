<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Relation;

use Netgen\Bundle\MoreBundle\Core\FieldType\RelationList\Value as RelationList;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Netgen\EzPlatformSiteApi\API\Values\Content;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

class LocationRelationResolver implements RelationResolverInterface
{
    /**
     * @var \Netgen\EzPlatformSiteApi\API\LoadService
     */
    protected $loadService;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(LoadService $loadService, LoggerInterface $logger = null)
    {
        $this->loadService = $loadService;
        $this->logger = $logger ?: new NullLogger();
    }

    public function loadRelations(Content $content, string $fieldIdentifier): array
    {
        $relatedItems = [];

        if (!$content->hasField($fieldIdentifier)) {
            return $relatedItems;
        }

        $field = $content->getField($fieldIdentifier);
        if (!$field->value instanceof RelationList || $field->isEmpty()) {
            return $relatedItems;
        }

        foreach ($field->value->destinationLocationIds as $locationId) {
            try {
                $location = $this->loadService->loadLocation((int) $locationId);
            } catch (Throwable $t) {
                // Do nothing if there's no location or we're not authorized to load it
                $this->logger->error(
                    sprintf('Error while loading location relation with #%s in content #%s', $locationId, $content->id),
                    ['error' => $t]
                );

                continue;
            }

            if ($location->invisible || !$location->contentInfo->published) {
                continue;
            }

            $relatedItems[] = $location;
        }

        return $relatedItems;
    }
}
