<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Relation;

use Netgen\EzPlatformSiteApi\API\Values\Location;

interface LocationRelationResolverInterface
{
    public function loadRelations(Location $location, string $fieldIdentifier = null, array $options = []): array;
}
