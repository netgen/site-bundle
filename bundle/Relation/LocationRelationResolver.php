<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Relation;

use eZ\Publish\Core\FieldType\RelationList\Value as RelationList;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use function is_string;
use function sprintf;

class LocationRelationResolver implements LocationRelationResolverInterface
{
    protected LoadService $loadService;

    protected LoggerInterface $logger;

    public function __construct(LoadService $loadService, ?LoggerInterface $logger = null)
    {
        $this->loadService = $loadService;
        $this->logger = $logger ?? new NullLogger();
    }

    public function loadRelations(Location $location, ?string $fieldIdentifier = null, array $options = []): array
    {
        $relatedItems = [];

        $content = $location->content;
        if (!is_string($fieldIdentifier) || !$content->hasField($fieldIdentifier)) {
            return $relatedItems;
        }

        $field = $content->getField($fieldIdentifier);
        if (!$field->value instanceof RelationList || $field->isEmpty()) {
            return $relatedItems;
        }

        foreach ($field->value->destinationContentIds as $destinationContentId) {
            try {
                $destinationLocation = $this->loadService->loadContent((int) $destinationContentId)->mainLocation;
            } catch (Throwable $t) {
                // Do nothing if there's no location or we're not authorized to load it
                $this->logger->error(
                    sprintf('Error while loading content relation with #%s in content #%s', $destinationContentId, $content->id),
                    ['error' => $t],
                );

                continue;
            }

            if (!$destinationLocation->isVisible || !$destinationLocation->contentInfo->published) {
                continue;
            }

            $relatedItems[] = $destinationLocation;
        }

        return $relatedItems;
    }
}
