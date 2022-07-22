<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Core\FieldType\RelationList;

use eZ\Publish\Core\Base\Exceptions\InvalidArgumentType;
use eZ\Publish\Core\FieldType\RelationList\Type as BaseRelationListType;
use eZ\Publish\Core\FieldType\RelationList\Value as BaseRelationListValue;
use eZ\Publish\Core\FieldType\Value as BaseValue;
use eZ\Publish\SPI\FieldType\Value as SPIValue;

use function is_array;
use function is_int;
use function is_string;
use function sprintf;

class Type extends BaseRelationListType
{
    public function getEmptyValue(): SPIValue
    {
        return new Value();
    }

    public function fromHash($hash): SPIValue
    {
        return new Value(
            $hash['destinationContentIds'],
            $hash['destinationLocationIds'] ?? [],
        );
    }

    public function toHash(SPIValue $value): array
    {
        return [
            'destinationContentIds' => $value->destinationContentIds,
            'destinationLocationIds' => $value->destinationLocationIds,
        ];
    }

    protected function createValueFromInput($inputValue)
    {
        if (is_array($inputValue) && isset($inputValue['relation_list'])) {
            $relationList = $inputValue['relation_list'];
            if (!is_array($relationList)) {
                return $inputValue;
            }

            $destinationContentIds = [];
            $destinationLocationIds = [];

            foreach ($relationList as $relationListItem) {
                if (!isset($relationListItem['content_id'], $relationListItem['location_id'])) {
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

        $parentValue = parent::createValueFromInput($inputValue);

        if (
            $parentValue instanceof BaseRelationListValue
            && !$parentValue instanceof Value
        ) {
            return new Value($parentValue->destinationContentIds);
        }

        return $parentValue;
    }

    protected static function checkValueType($value): void
    {
        if ($value instanceof BaseRelationListValue) {
            // Includes overridden type too
            return;
        }

        throw new InvalidArgumentType(
            '$value',
            sprintf(
                '"%s" or "%s"',
                BaseRelationListValue::class,
                Value::class,
            ),
            $value,
        );
    }

    protected function checkValueStructure(BaseValue $value): void
    {
        parent::checkValueStructure($value);

        if (!is_array($value->destinationLocationIds)) {
            throw new InvalidArgumentType(
                '$value->destinationLocationIds',
                'array',
                $value->destinationLocationIds,
            );
        }

        foreach ($value->destinationLocationIds as $key => $destinationLocationId) {
            if (!is_int($destinationLocationId) && !is_string($destinationLocationId)) {
                throw new InvalidArgumentType(
                    '$value->destinationLocationIds[$key]',
                    'string|int',
                    $destinationLocationId,
                );
            }
        }
    }
}
