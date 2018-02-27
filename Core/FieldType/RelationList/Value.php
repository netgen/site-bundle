<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Core\FieldType\RelationList;

use eZ\Publish\Core\FieldType\RelationList\Value as BaseRelationListValue;

class Value extends BaseRelationListValue
{
    /**
     * Related location IDs.
     *
     * @var mixed[]
     */
    public $destinationLocationIds = array();

    public function __construct(array $destinationContentIds = array(), array $destinationLocationIds = array())
    {
        parent::__construct($destinationContentIds);

        $this->destinationLocationIds = $destinationLocationIds;
    }
}
