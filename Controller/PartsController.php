<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;

class PartsController extends Controller
{
    /**
     * Action for rendering the ng_article gallery
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     * @param string $template
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewGallery( Location $location, $template )
    {
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
}
