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
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException If block has an invalid type or parent_node
     *         custom attribute is missing
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

        $defaultLimit = 10;
        if ( isset( $params['itemLimit'] ) )
        {
            $itemLimit = (int)$params['itemLimit'];
            if ( $itemLimit > 0 )
            {
                $defaultLimit = $itemLimit;
            }
        }

        $offset = isset( $block->customAttributes['offset'] ) ? (int)$block->customAttributes['offset'] : 0;
        $limit = isset( $block->customAttributes['limit'] ) ? (int)$block->customAttributes['limit'] : $defaultLimit;

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
                        'locationId' => $searchHit->valueObject->id
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
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException If advanced sort field has an invalid value
     *
     * @return array
     */
    protected function getSortClause( $sortField, $advancedSortField, $sortOrder, Location $parentLocation )
    {
        $sortClauseHelper = $this->container->get( 'netgen_more.helper.sort_clause_helper' );

        if ( $sortField === 'parent_node_sort_array' )
        {
            return $sortClauseHelper->getSortClauseBySortField(
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
                $sortClauseHelper->getQuerySortOrder( $sortOrder ),
                $fieldDefinition->isTranslatable ? $currentLanguage : null
            );
        }

        return $sortClauseHelper->getSortClauseBySortField( $sortField );
    }
}
