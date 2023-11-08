<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Relation;

use Ibexa\Core\FieldType\RelationList\Value as RelationList;
use Netgen\IbexaSiteApi\API\LoadService;
use Netgen\IbexaSiteApi\API\Values\Location;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

use function is_string;
use function sprintf;

final class LocationRelationResolver implements LocationRelationResolverInterface
{
    public function __construct(private LoadService $loadService, private LoggerInterface $logger = new NullLogger()) {}

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
                /** @var \Netgen\IbexaSiteApi\API\Values\Location $destinationLocation */
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
