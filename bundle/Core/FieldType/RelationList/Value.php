<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Core\FieldType\RelationList;

use eZ\Publish\Core\FieldType\RelationList\Value as BaseRelationListValue;

class Value extends BaseRelationListValue
{
    /**
     * Related location IDs.
     *
     * @var mixed[]
     */
    public array $destinationLocationIds = [];

    public function __construct(array $destinationContentIds = [], array $destinationLocationIds = [])
    {
        parent::__construct($destinationContentIds);

        $this->destinationLocationIds = $destinationLocationIds;
    }
}
