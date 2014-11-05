<?php

namespace Netgen\Bundle\MoreBundle\Core\FieldType\RelationList;

use eZ\Publish\Core\FieldType\RelationList\Type as BaseRelationListType;

use eZ\Publish\Core\Base\Exceptions\InvalidArgumentType;
use eZ\Publish\SPI\FieldType\Value as SPIValue;
use eZ\Publish\Core\FieldType\Value as BaseValue;

class Type extends BaseRelationListType
{
    /**
     * Returns the fallback default value of field type when no such default
     * value is provided in the field definition in content types.
     *
     * @return \Netgen\Bundle\MoreBundle\Core\FieldType\RelationList\Value
     */
    public function getEmptyValue()
    {
        return new Value();
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param int|string|array|\eZ\Publish\API\Repository\Values\Content\ContentInfo|\eZ\Publish\Core\FieldType\RelationList\Value $inputValue
     *
     * @return \Netgen\Bundle\MoreBundle\Core\FieldType\RelationList\Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput( $inputValue )
    {
        if ( is_array( $inputValue ) && isset( $inputValue['relation_list'] ) )
        {
            $relationList = $inputValue['relation_list'];
            if ( !is_array( $relationList ) )
            {
                return $inputValue;
            }

            $destinationContentIds = array();
            $destinationLocationIds = array();

            foreach ( $relationList as $relationListItem )
            {
                if ( !isset( $relationListItem['content_id'] ) || !isset( $relationListItem['location_id'] ) )
                {
                    return $inputValue;
                }

                if ( is_integer( $relationListItem['content_id'] ) || is_string( $relationListItem['content_id'] ) )
                {
                    return $inputValue;
                }

                if ( is_integer( $relationListItem['location_id'] ) || is_string( $relationListItem['location_id'] ) )
                {
                    return $inputValue;
                }

                $destinationContentIds[] = $relationListItem['content_id'];
                $destinationLocationIds[] = $relationListItem['location_id'];
            }

            return new Value( $destinationContentIds, $destinationLocationIds );
        }

        return parent::createValueFromInput( $inputValue );
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \Netgen\Bundle\MoreBundle\Core\FieldType\RelationList\Value $value
     */
    protected function checkValueStructure( BaseValue $value )
    {
        parent::checkValueStructure( $value );

        if ( !is_array( $value->destinationLocationIds ) )
        {
            throw new InvalidArgumentType(
                "\$value->destinationLocationIds",
                'array',
                $value->destinationLocationIds
            );
        }

        foreach ( $value->destinationLocationIds as $key => $destinationLocationId )
        {
            if ( !is_integer( $destinationLocationId ) && !is_string( $destinationLocationId ) )
            {
                throw new InvalidArgumentType(
                    "\$value->destinationLocationIds[$key]",
                    'string|int',
                    $destinationLocationId
                );
            }
        }
    }

    /**
     * Converts an $hash to the Value defined by the field type
     *
     * @param mixed $hash
     *
     * @return \Netgen\Bundle\MoreBundle\Core\FieldType\RelationList\Value $value
     */
    public function fromHash( $hash )
    {
        return new Value( $hash['destinationContentIds'], $hash['destinationLocationIds'] );
    }

    /**
     * Converts a $Value to a hash
     *
     * @param \Netgen\Bundle\MoreBundle\Core\FieldType\RelationList\Value $value
     *
     * @return mixed
     */
    public function toHash( SPIValue $value )
    {
        return array(
            'destinationContentIds' => $value->destinationContentIds,
            'destinationLocationIds' => $value->destinationLocationIds
        );
    }
}
