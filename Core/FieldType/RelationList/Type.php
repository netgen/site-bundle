<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Core\FieldType\RelationList;

use eZ\Publish\Core\Base\Exceptions\InvalidArgumentType;
use eZ\Publish\Core\FieldType\RelationList\Type as BaseRelationListType;
use eZ\Publish\Core\FieldType\Value as BaseValue;
use eZ\Publish\SPI\FieldType\Value as SPIValue;

class Type extends BaseRelationListType
{
    public function getEmptyValue(): Value
    {
        return new Value();
    }

    public function fromHash($hash): Value
    {
        return new Value(
            $hash['destinationContentIds'],
            $hash['destinationLocationIds'] ?? array()
        );
    }

    public function toHash(SPIValue $value): array
    {
        return array(
            'destinationContentIds' => $value->destinationContentIds,
            'destinationLocationIds' => $value->destinationLocationIds,
        );
    }

    protected function createValueFromInput($inputValue)
    {
        if (is_array($inputValue) && isset($inputValue['relation_list'])) {
            $relationList = $inputValue['relation_list'];
            if (!is_array($relationList)) {
                return $inputValue;
            }

            $destinationContentIds = array();
            $destinationLocationIds = array();

            foreach ($relationList as $relationListItem) {
                if (!isset($relationListItem['content_id']) || !isset($relationListItem['location_id'])) {
                    return $inputValue;
                }

                if (!is_int($relationListItem['content_id']) && !is_string($relationListItem['content_id'])) {
                    return $inputValue;
                }

                if (!is_int($relationListItem['location_id']) && !is_string($relationListItem['location_id'])) {
                    return $inputValue;
                }

                $destinationContentIds[] = $relationListItem['content_id'];
                $destinationLocationIds[] = $relationListItem['location_id'];
            }

            return new Value($destinationContentIds, $destinationLocationIds);
        }

        return parent::createValueFromInput($inputValue);
    }

    protected function checkValueStructure(BaseValue $value): void
    {
        parent::checkValueStructure($value);

        if (!is_array($value->destinationLocationIds)) {
            throw new InvalidArgumentType(
                '$value->destinationLocationIds',
                'array',
                $value->destinationLocationIds
            );
        }

        foreach ($value->destinationLocationIds as $key => $destinationLocationId) {
            if (!is_int($destinationLocationId) && !is_string($destinationLocationId)) {
                throw new InvalidArgumentType(
                    "\$value->destinationLocationIds[$key]",
                    'string|int',
                    $destinationLocationId
                );
            }
        }
    }
}
