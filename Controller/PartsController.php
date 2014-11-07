<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;

class PartsController extends Controller
{
    /**
     * Action for rendering the gallery
     *
     * @param mixed $locationId
     * @param string $template
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewGallery( $locationId, $template )
    {
        $location = $this->getRepository()->getLocationService()->loadLocation( $locationId );

        $contentService = $this->getRepository()->getContentService();
        $content = $contentService->loadContent( $location->contentId );
        $fieldHelper = $this->container->get( 'ezpublish.field_helper' );

        $query = new LocationQuery();

        $criterions = array(
            new Criterion\Subtree( $location->pathString ),
            new Criterion\Visibility( Criterion\Visibility::VISIBLE ),
            new Criterion\LogicalNot( new Criterion\LocationId( $location->id ) ),
            new Criterion\Location\Depth( Criterion\Operator::EQ, $location->depth + 1 ),
            new Criterion\ContentTypeIdentifier( 'image' )
        );

        $query->criterion = new Criterion\LogicalAnd( $criterions );

        $query->sortClauses = array(
            $this->container->get( 'netgen_more.helper.sort_clause_helper' )->getSortClauseBySortField(
                $location->sortField,
                $location->sortOrder
            )
        );

        $result = $this->getRepository()->getSearchService()->findLocations( $query );

        $contentList = array();
        foreach ( $result->searchHits as $searchHit )
        {
            $contentList[] = $contentService->loadContent( $searchHit->valueObject->contentId );
        }

        if ( !$fieldHelper->isFieldEmpty( $content, 'image' ) )
        {
            array_unshift( $contentList, $content );
        }

        return $this->render(
            $template,
            array(
                'content_list' => $contentList
            )
        );
    }

    /**
     * Action for rendering related items
     *
     * @param mixed $contentId
     * @param string $fieldDefinitionIdentifier
     * @param string $viewType
     * @param string $template
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewRelatedItems( $contentId, $fieldDefinitionIdentifier, $template, $viewType = 'line' )
    {
        $relatedItems = array();

        $fieldHelper = $this->container->get( 'ezpublish.field_helper' );
        $translationHelper = $this->container->get( 'ezpublish.translation_helper' );
        $locationService = $this->getRepository()->getLocationService();

        $content = $this->getRepository()->getContentService()->loadContent( $contentId );

        if ( isset( $content->fields[$fieldDefinitionIdentifier] ) && !$fieldHelper->isFieldEmpty( $content, $fieldDefinitionIdentifier ) )
        {
            /** @var \Netgen\Bundle\MoreBundle\Core\FieldType\RelationList\Value $fieldValue */
            $fieldValue = $translationHelper->getTranslatedField( $content, $fieldDefinitionIdentifier )->value;
            if ( !empty( $fieldValue->destinationLocationIds ) )
            {
                foreach ( $fieldValue->destinationLocationIds as $locationId )
                {
                    try
                    {
                        $location = $locationService->loadLocation( $locationId );
                        if ( !$location->invisible )
                        {
                            $relatedItems[] = $location;
                        }
                    }
                    catch ( NotFoundException $e )
                    {
                        // Do nothing if there's no location
                    }
                }
            }
        }

        return $this->render(
            $template,
            array(
                'related_items' => $relatedItems,
                'view_type' => $viewType
            )
        );
    }
}
