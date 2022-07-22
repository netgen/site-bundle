<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Core\Persistence\Legacy\Content\FieldValue\Converter;

use DOMDocument;
use eZ\Publish\Core\Persistence\Legacy\Content\FieldValue\Converter\RelationListConverter as BaseRelationListConverter;
use eZ\Publish\Core\Persistence\Legacy\Content\StorageFieldDefinition;
use eZ\Publish\Core\Persistence\Legacy\Content\StorageFieldValue;
use eZ\Publish\SPI\Persistence\Content\FieldValue;
use eZ\Publish\SPI\Persistence\Content\Type\FieldDefinition;

use function array_keys;
use function asort;

use const SORT_NUMERIC;

class RelationListConverter extends BaseRelationListConverter
{
    public function toStorageValue(FieldValue $value, StorageFieldValue $storageFieldValue): void
    {
        $doc = new DOMDocument('1.0', 'utf-8');
        $root = $doc->createElement('related-objects');
        $doc->appendChild($root);

        $relationList = $doc->createElement('relation-list');
        $data = $this->getRelationXmlHashFromDB($value->data['destinationContentIds']);
        $priority = 0;

        foreach ($value->data['destinationContentIds'] as $key => $id) {
            if (!isset($data[$id][0])) {
                // Ignore deleted content items (we can't throw as it would block ContentService->createContentDraft())
                continue;
            }

            $row = $data[$id][0];
            $row['ezcontentobject_id'] = $id;
            $row['priority'] = (++$priority);

            if (!empty($value->data['destinationLocationIds'][$key])) {
                $row['ezcontentobject_tree_node_id'] = $value->data['destinationLocationIds'][$key];
            }

            $relationItem = $doc->createElement('relation-item');
            foreach (self::dbAttributeMap() as $domAttrKey => $propertyKey) {
                if (!isset($row[$propertyKey])) {
                    // left join data missing, ignore the given attribute (content in trash missing location)
                    continue;
                }

                $relationItem->setAttribute($domAttrKey, (string) $row[$propertyKey]);
            }
            $relationList->appendChild($relationItem);
            unset($relationItem);
        }

        $root->appendChild($relationList);
        $doc->appendChild($root);

        $storageFieldValue->dataText = $doc->saveXML();
    }

    public function toFieldValue(StorageFieldValue $value, FieldValue $fieldValue): void
    {
        $fieldValue->data = [
            'destinationContentIds' => [],
            'destinationLocationIds' => [],
        ];

        if ($value->dataText === null) {
            return;
        }

        $priorityByContentId = [];
        $priorityByLocationId = [];

        $dom = new DOMDocument('1.0', 'utf-8');
        if ($dom->loadXML($value->dataText) === true) {
            foreach ($dom->getElementsByTagName('relation-item') as $relationItem) {
                /* @var \DOMElement $relationItem */
                $priorityByContentId[$relationItem->getAttribute('contentobject-id')] =
                    $relationItem->getAttribute('priority');

                $priorityByLocationId[$relationItem->getAttribute('node-id')] =
                    $relationItem->getAttribute('priority');
            }
        }

        asort($priorityByContentId, SORT_NUMERIC);
        asort($priorityByLocationId, SORT_NUMERIC);

        $fieldValue->data['destinationContentIds'] = array_keys($priorityByContentId);
        $fieldValue->data['destinationLocationIds'] = array_keys($priorityByLocationId);
    }

    public function toFieldDefinition(StorageFieldDefinition $storageDef, FieldDefinition $fieldDef): void
    {
        parent::toFieldDefinition($storageDef, $fieldDef);

        $fieldDef->defaultValue->data['destinationLocationIds'] = [];
    }

    private static function dbAttributeMap(): array
    {
        return [
            // 'identifier' => 'identifier',// not used
            'priority' => 'priority',
            // 'in-trash' => 'in_trash',// false by default and implies
            'contentobject-id' => 'ezcontentobject_id',
            'contentobject-version' => 'ezcontentobject_current_version',
            'node-id' => 'ezcontentobject_tree_node_id',
            'parent-node-id' => 'ezcontentobject_tree_parent_node_id',
            'contentclass-id' => 'ezcontentobject_contentclass_id',
            'contentclass-identifier' => 'ezcontentclass_identifier',
            // 'is-modified' => 'is_modified',// deprecated and not used
            'contentobject-remote-id' => 'ezcontentobject_remote_id',
        ];
    }
}
