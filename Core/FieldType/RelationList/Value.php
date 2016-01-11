<?php

namespace Netgen\Bundle\MoreBundle\Core\FieldType\RelationList;

use eZ\Publish\Core\FieldType\RelationList\Value as BaseRelationListValue;

/**
 * Value for RelationList field type.
 */
class Value extends BaseRelationListValue
{
    /**
     * Related location IDs.
     *
     * @var mixed[]
     */
    public $destinationLocationIds;

    /**
     * Construct a new Value object and initialize it $text.
     *
     * @param mixed[] $destinationContentIds
     * @param mixed[] $destinationLocationIds
     */
    public function __construct(array $destinationContentIds = array(), array $destinationLocationIds = array())
    {
        parent::__construct($destinationContentIds);

        $this->destinationLocationIds = $destinationLocationIds;
    }
}
