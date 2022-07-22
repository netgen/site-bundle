<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Relation;

use Netgen\EzPlatformSiteApi\API\LoadService;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function array_merge;
use function iterator_to_array;

class MultimediaRelationResolver implements LocationRelationResolverInterface
{
    protected LoadService $loadService;

    protected LocationRelationResolverInterface $innerResolver;

    public function __construct(LoadService $loadService, LocationRelationResolverInterface $innerResolver)
    {
        $this->loadService = $loadService;
        $this->innerResolver = $innerResolver;
    }

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
            $multimediaItems[] = iterator_to_array($children->getCurrentPageResults());
        }

        $relatedMultimedia = $this->innerResolver->loadRelations($location, $fieldIdentifier);
        foreach ($relatedMultimedia as $relatedMultimediaItem) {
            if ($relatedMultimediaItem->contentInfo->contentTypeIdentifier === 'ng_gallery') {
                // For galleries, find children objects and add them in multimedia item list
                $children = $relatedMultimediaItem->filterChildren($options['content_types']);
                $multimediaItems[] = iterator_to_array($children->getCurrentPageResults());
            } else {
                $multimediaItems[] = [$relatedMultimediaItem];
            }
        }

        return array_merge(...$multimediaItems);
    }

    protected function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setRequired('include_children');
        $optionsResolver->setAllowedTypes('include_children', 'bool');
        $optionsResolver->setDefault('include_children', false);

        $optionsResolver->setRequired('content_types');
        $optionsResolver->setAllowedTypes('content_types', 'array');
        $optionsResolver->setDefault('content_types', ['image']);
    }
}
