<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\Search\SearchHit;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\Core\FieldType\Page\Parts\Item;

class BlockViewController extends Controller
{
    /**
     * Renders the ContentGridDynamic block with given $id.
     *
     * This method can be used with ESI rendering strategy.
     *
     * @param mixed $id Block id
     * @param array $params
     * @param array $cacheSettings settings for the HTTP cache, 'smax-age' and
     *              'max-age' are checked.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewContentGridDynamicBlockById( $id, array $params = array(), array $cacheSettings = array() )
    {
        $block = $this->container->get( 'ezpublish.fieldType.ezpage.pageService' )->loadBlock( $id );
        if ( $block->type !== 'ContentGridDynamic' )
        {
            throw new InvalidArgumentException(
                'id',
                'Block #' . $id . ' has an invalid type. Expected "ContentGridDynamic", got "' . $block->type . '"'
            );
        }

        if ( !isset( $block->customAttributes['parent_node'] ) )
        {
            throw new InvalidArgumentException(
                'parent_node',
                'Block #' . $id . ' is missing "parent_node" custom attribute.'
            );
        }

        $parentLocationId = $block->customAttributes['parent_node'];
        $parentLocation = $this->getRepository()->getLocationService()->loadLocation( $parentLocationId );

        $offset = isset( $block->customAttributes['offset'] ) ? (int)$block->customAttributes['offset'] : 0;
        $limit = isset( $block->customAttributes['limit'] ) ? (int)$block->customAttributes['limit'] : 10;

        $sortField = isset( $block->customAttributes['advanced_order'] ) ?
            $block->customAttributes['advanced_order'] : 'parent_node_sort_array';

        $advancedSortField = isset( $block->customAttributes['advanced_custom_order'] ) ?
            $block->customAttributes['advanced_custom_order'] : null;

        $sortOrder = Query::SORT_ASC;
        if ( isset( $block->customAttributes['advanced_order_direction'] )
             && $block->customAttributes['advanced_order_direction'] == 'desc' )
        {
            $sortOrder = Query::SORT_DESC;
        }

        $criterions = array(
            new Criterion\Subtree( $parentLocation->pathString ),
            new Criterion\Visibility( Criterion\Visibility::VISIBLE ),
            new Criterion\LogicalNot( new Criterion\LocationId( $parentLocation->id ) )
        );

        if ( !isset( $block->customAttributes['advanced_fetch_type'] )
            || $block->customAttributes['advanced_fetch_type'] == 'list' )
        {
            $criterions[] = new Criterion\Location\Depth( Criterion\Operator::EQ, $parentLocation->depth + 1 );
        }

        if ( !empty( $block->customAttributes['advanced_class_filter_array'] ) )
        {
            $contentTypeFilterCriterion = $this->getContentTypeFilterCriterion(
                explode( ',', $block->customAttributes['advanced_class_filter_array'] ),
                isset( $block->customAttributes['advanced_class_filter_type'] ) ?
                    $block->customAttributes['advanced_class_filter_type'] :
                    'include'
            );

            $criterions[] = $contentTypeFilterCriterion;
        }

        $query = new LocationQuery();
        $query->filter = new Criterion\LogicalAnd( $criterions );
        $query->sortClauses = array(
            $this->getSortClause( $sortField, $advancedSortField, $sortOrder, $parentLocation )
        );
        $query->limit = $limit;
        $query->offset = $offset;

        $result = $this->getRepository()->getSearchService()->findLocations( $query );

        $validItems = array_map(
            function ( SearchHit $searchHit ) use ( $block )
            {
                return new Item(
                    array(
                        'blockId' => $block->id,
                        'contentId' => $searchHit->valueObject->contentInfo->id,
                        'locationId' => $searchHit->valueObject->id,
                        'priority' => $searchHit->valueObject->priority
                    )
                );
            },
            $result->searchHits
        );

        $response = $this->container->get( 'ez_page' )->viewBlockById(
            $id,
            array(
                'valid_items' => $validItems
            ) + $params,
            $cacheSettings
        );

        $response->headers->set( 'X-Location-Id', $block->customAttributes['parent_node'] );

        return $response;
    }

    /**
     * Returns content type criterions for block, generated from 'advanced_class_filter_type'
     * and 'advanced_class_filter_array' custom attributes
     *
     * @param array $contentTypeIdentifiers
     * @param string $filterType
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Query\CriterionInterface
     */
    protected function getContentTypeFilterCriterion( array $contentTypeIdentifiers, $filterType )
    {
        $contentTypeCriterion = new Criterion\ContentTypeIdentifier(
            array_map(
                'trim',
                $contentTypeIdentifiers
            )
        );

        if ( $filterType == 'exclude' )
        {
            return new Criterion\LogicalNot( $contentTypeCriterion );
        }

        return $contentTypeCriterion;
    }

    /**
     * Returns the sort clause for block, collected from 'advanced_order', 'advanced_custom_order'
     * and 'advanced_order_direction' custom attributes
     *
     * @param string $sortField
     * @param string $advancedSortField
     * @param string $sortOrder
     * @param \eZ\Publish\API\Repository\Values\Content\Location $parentLocation
     *
     * @return array
     */
    protected function getSortClause( $sortField, $advancedSortField, $sortOrder, Location $parentLocation )
    {
        if ( $sortField === 'parent_node_sort_array' )
        {
            return $this->getSortClauseBySortField(
                $parentLocation->sortField,
                $parentLocation->sortOrder
            );
        }
        else if ( $sortField === 'attribute' )
        {
            $advancedSortFieldArray = explode( '/', $advancedSortField );
            if ( empty( $advancedSortFieldArray[0] ) || empty( $advancedSortFieldArray[1] ) )
            {
                throw new InvalidArgumentException( 'advanced_custom_order', 'Custom sorting field value "' . $advancedSortField . '" is invalid.' );
            }

            $contentType = $this->getRepository()->getContentTypeService()->loadContentTypeByIdentifier(
                $advancedSortFieldArray[0]
            );
            $fieldDefinition = $contentType->getFieldDefinition( $advancedSortFieldArray[1] );

            $availableLanguages = $this->getConfigResolver()->getParameter( 'languages' );
            $currentLanguage = $availableLanguages[0];

            return new SortClause\Field(
                $advancedSortFieldArray[0],
                $advancedSortFieldArray[1],
                $this->getQuerySortOrder( $sortOrder ),
                $fieldDefinition->isTranslatable ? $currentLanguage : null
            );
        }

        return $this->getSortClauseBySortField( $sortField );
    }

    /**
     * Returns query sort order by location sort order
     *
     * @param int $sortOrder
     *
     * @return string
     */
    protected function getQuerySortOrder( $sortOrder )
    {
        if ( $sortOrder === Location::SORT_ORDER_DESC )
        {
            return Query::SORT_DESC;
        }

        return Query::SORT_ASC;
    }

    /**
     * Instantiates a correct sort clause object based on provided location sort field and sort order
     *
     * @param int $sortField
     * @param int $sortOrder
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Query\SortClause
     */
    protected function getSortClauseBySortField( $sortField, $sortOrder = Location::SORT_ORDER_ASC )
    {
        switch ( $sortField )
        {
            case 'path':
            case 'path_string':
            case Location::SORT_FIELD_PATH:
                return new SortClause\Location\Path( $this->getQuerySortOrder( $sortOrder ) );

            case 'published':
            case Location::SORT_FIELD_PUBLISHED:
                return new SortClause\DatePublished( $this->getQuerySortOrder( $sortOrder ) );

            case 'modified':
            case Location::SORT_FIELD_MODIFIED:
                return new SortClause\DateModified( $this->getQuerySortOrder( $sortOrder ) );

            case 'section':
            case Location::SORT_FIELD_SECTION:
                return new SortClause\SectionIdentifier( $this->getQuerySortOrder( $sortOrder ) );

            case 'depth':
            case Location::SORT_FIELD_DEPTH:
                return new SortClause\Location\Depth( $this->getQuerySortOrder( $sortOrder ) );

            //@todo: sort clause not yet implemented
            // case 'class_identifier'
            // case Location::SORT_FIELD_CLASS_IDENTIFIER:

            //@todo: sort clause not yet implemented
            // case 'class_name'
            // case Location::SORT_FIELD_CLASS_NAME:

            case 'priority':
            case Location::SORT_FIELD_PRIORITY:
                return new SortClause\Location\Priority( $this->getQuerySortOrder( $sortOrder ) );

            case 'name':
            case Location::SORT_FIELD_NAME:
                return new SortClause\ContentName( $this->getQuerySortOrder( $sortOrder ) );

            //@todo: sort clause not yet implemented
            // case Location::SORT_FIELD_MODIFIED_SUBNODE:

            case Location::SORT_FIELD_NODE_ID:
                return new SortClause\Location\Id( $this->getQuerySortOrder( $sortOrder ) );

            case Location::SORT_FIELD_CONTENTOBJECT_ID:
                return new SortClause\ContentId( $this->getQuerySortOrder( $sortOrder ) );

            default:
                return new SortClause\Location\Path( $this->getQuerySortOrder( $sortOrder ) );
        }
    }
}
