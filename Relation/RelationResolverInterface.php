<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Relation;

use Netgen\EzPlatformSiteApi\API\Values\Content;

interface RelationResolverInterface
{
    public function loadRelations(Content $content, string $fieldIdentifier): array;
}
