<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Relation;

use Netgen\EzPlatformSiteApi\API\Values\Location;

interface LocationRelationResolverInterface
{
    /**
     * Returns the list of locations related to the provided location.
     *
     * @param \Netgen\EzPlatformSiteApi\API\Values\Location $location
     * @param string $fieldIdentifier
     * @param array $options
     *
     * @return \Netgen\EzPlatformSiteApi\API\Values\Location[]
     */
    public function loadRelations(Location $location, string $fieldIdentifier = null, array $options = []): array;
}
