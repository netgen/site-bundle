<?php

namespace Netgen\Bundle\MoreBundle\Core\Persistence\Legacy\Content\FieldValue\Converter;

use eZ\Publish\Core\Persistence\Legacy\Content\FieldValue\Converter\RelationList as BaseRelationListConverter;

use eZ\Publish\SPI\Persistence\Content\Type\FieldDefinition;
use eZ\Publish\Core\Persistence\Legacy\Content\StorageFieldDefinition;
use eZ\Publish\Core\Persistence\Legacy\Content\StorageFieldValue;
use eZ\Publish\SPI\Persistence\Content\FieldValue;
use DOMDocument;

class RelationList extends BaseRelationListConverter
{
    /**
     * @var \eZ\Publish\Core\Persistence\Database\DatabaseHandler
     */
    protected $db;

    /**
     * Converts data from $value to $storageFieldValue
     *
     * @param \eZ\Publish\SPI\Persistence\Content\FieldValue $value
     * @param \eZ\Publish\Core\Persistence\Legacy\Content\StorageFieldValue $storageFieldValue
     */
    public function toStorageValue( FieldValue $value, StorageFieldValue $storageFieldValue )
    {
        $doc = new DOMDocument( '1.0', 'utf-8' );
        $root = $doc->createElement( 'related-objects' );
        $doc->appendChild( $root );

        $relationList = $doc->createElement( 'relation-list' );
        $data = $this->getRelationXmlHashFromDB( $value->data['destinationContentIds'] );
        $priority = 0;

        foreach ( $value->data['destinationContentIds'] as $key => $id )
        {
            $row = $data[$id][0];
            $row["ezcontentobject_id"] = $id;
            $row["ezcontentobject_tree_node_id"] = $value->data['destinationLocationIds'][$key];
            $row["priority"] = ( $priority += 1 );

            $relationItem = $doc->createElement( 'relation-item' );
            foreach ( self::dbAttributeMap() as $domAttrKey => $propertyKey )
            {
                if ( !isset( $row[$propertyKey] ) )
                    throw new \RuntimeException( "Missing relation-item external data property: $propertyKey" );

                $relationItem->setAttribute( $domAttrKey, $row[$propertyKey] );
            }
            $relationList->appendChild( $relationItem );
            unset( $relationItem );
        }

        $root->appendChild( $relationList );
        $doc->appendChild( $root );

        $storageFieldValue->dataText = $doc->saveXML();
    }

    /**
     * Converts data from $value to $fieldValue
     *
     * @param \eZ\Publish\Core\Persistence\Legacy\Content\StorageFieldValue $value
     * @param \eZ\Publish\SPI\Persistence\Content\FieldValue $fieldValue
     */
    public function toFieldValue( StorageFieldValue $value, FieldValue $fieldValue )
    {
        $fieldValue->data = array(
            'destinationContentIds' => array(),
            'destinationLocationIds' => array()
        );

        if ( $value->dataText === null )
            return;

        $priorityByContentId = array();
        $priorityByLocationId = array();

        $dom = new DOMDocument( '1.0', 'utf-8' );
        if ( $dom->loadXML( $value->dataText ) === true )
        {
            foreach ( $dom->getElementsByTagName( 'relation-item' ) as $relationItem )
            {
                /** @var \DOMElement $relationItem */
                $priorityByContentId[$relationItem->getAttribute( 'contentobject-id' )] =
                    $relationItem->getAttribute( 'priority' );

                $priorityByLocationId[$relationItem->getAttribute( 'node-id' )] =
                    $relationItem->getAttribute( 'priority' );
            }
        }

        asort( $priorityByContentId, SORT_NUMERIC );
        asort( $priorityByLocationId, SORT_NUMERIC );

        $fieldValue->data['destinationContentIds'] = array_keys( $priorityByContentId );
        $fieldValue->data['destinationLocationIds'] = array_keys( $priorityByLocationId );
    }

    /**
     * Converts field definition data in $storageDef into $fieldDef
     *
     * <?xml version="1.0" encoding="utf-8"?>
     * <related-objects>
     *   <constraints>
     *     <allowed-class contentclass-identifier="blog_post"/>
     *   </constraints>
     *   <type value="2"/>
     *   <selection_type value="1"/>
     *   <object_class value=""/>
     *   <contentobject-placement node-id="67"/>
     * </related-objects>
     *
     * <?xml version="1.0" encoding="utf-8"?>
     * <related-objects>
     *   <constraints/>
     *   <type value="2"/>
     *   <selection_type value="0"/>
     *   <object_class value=""/>
     *   <contentobject-placement/>
     * </related-objects>
     *
     * @param \eZ\Publish\Core\Persistence\Legacy\Content\StorageFieldDefinition $storageDef
     * @param \eZ\Publish\SPI\Persistence\Content\Type\FieldDefinition $fieldDef
     */
    public function toFieldDefinition( StorageFieldDefinition $storageDef, FieldDefinition $fieldDef )
    {
        parent::toFieldDefinition( $storageDef, $fieldDef );

        $fieldDef->defaultValue->data['destinationLocationIds'] = array();
    }

    /**
     * @return array
     */
    static private function dbAttributeMap()
    {
        return array(
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
            'contentobject-remote-id' => 'ezcontentobject_remote_id'
        );
    }
}
