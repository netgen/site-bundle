<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Relation;

use Netgen\IbexaSiteApi\API\Values\Location;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_merge;

final class MultimediaRelationResolver implements LocationRelationResolverInterface
{
    public function __construct(
        private LocationRelationResolverInterface $innerResolver,
    ) {}

    public function loadRelations(Location $location, ?string $fieldIdentifier = null, array $options = []): array
    {
        $optionsResolver = new OptionsResolver();
        $this->configureOptions($optionsResolver);
        $options = $optionsResolver->resolve($options);

        // Add current location in the multimedia item list
        $multimediaItems = [[$location]];

        // Get children objects and add them in multimedia item list
        if ($options['include_children']) {
            $children = $location->filterChildren($options['content_types']);
            $multimediaItems[] = [...$children->getCurrentPageResults()];
        }

        $relatedMultimedia = $this->innerResolver->loadRelations($location, $fieldIdentifier);
        foreach ($relatedMultimedia as $relatedMultimediaItem) {
            if ($relatedMultimediaItem->contentInfo->contentTypeIdentifier === 'ng_gallery') {
                // For galleries, find children objects and add them in multimedia item list
                $children = $relatedMultimediaItem->filterChildren($options['content_types']);
                $multimediaItems[] = [...$children->getCurrentPageResults()];
            } else {
                $multimediaItems[] = [$relatedMultimediaItem];
            }
        }

        return array_merge(...$multimediaItems);
    }

    private function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setRequired('include_children');
        $optionsResolver->setAllowedTypes('include_children', 'bool');
        $optionsResolver->setDefault('include_children', false);

        $optionsResolver->setRequired('content_types');
        $optionsResolver->setAllowedTypes('content_types', 'array');
        $optionsResolver->setDefault('content_types', ['image']);
    }
}
