<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use eZ\Publish\Core\FieldType\Relation\Value as RelationValue;
use eZ\Publish\Core\FieldType\Url\Value as UrlValue;
use eZ\Publish\Core\Pagination\Pagerfanta\LocationSearchAdapter;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use Pagerfanta\Pagerfanta;

class FullViewController extends Controller
{
    /**
     * Action for viewing location with ng_category content type identifier
     *
     * @param mixed $locationId
     * @param string $viewType
     * @param boolean $layout
     * @param array $params
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewNgCategoryLocation( $locationId, $viewType, $layout = false, array $params = array() )
    {
        $response = $this->checkCategoryRedirect( $locationId );
        if ( $response instanceof Response )
        {
            return $response;
        }

        $featuredLocations = array();

        $location = $this->getRepository()->getLocationService()->loadLocation( $locationId );

        $criterions = array(
            new Criterion\Subtree( $location->pathString ),
            new Criterion\Visibility( Criterion\Visibility::VISIBLE ),
            new Criterion\LogicalNot( new Criterion\LocationId( $location->id ) )
        );

        $query = new LocationQuery();
        $query->criterion = new Criterion\LogicalAnd( $criterions );

        $pager = new Pagerfanta(
            new LocationSearchAdapter(
                $query,
                $this->getRepository()->getSearchService()
            )
        );

        return $this->get( 'ez_content' )->viewLocation(
            $locationId,
            $viewType,
            $layout,
            $params + array(
                'featured_locations' => $featuredLocations,
                'pager' => $pager
            )
        );
    }

    /**
     * Action for viewing location with ng_landing_page content type identifier
     *
     * @param mixed $locationId
     * @param string $viewType
     * @param boolean $layout
     * @param array $params
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewNgLandingPageLocation( $locationId, $viewType, $layout = false, array $params = array() )
    {
        $response = $this->checkCategoryRedirect( $locationId );
        if ( $response instanceof Response )
        {
            return $response;
        }

        return $this->get( 'ez_content' )->viewLocation( $locationId, $viewType, $layout, $params );
    }

    /**
     * Action for viewing location with ng_category_page content type identifier
     *
     * @param mixed $locationId
     * @param string $viewType
     * @param boolean $layout
     * @param array $params
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewNgCategoryPageLocation( $locationId, $viewType, $layout = false, array $params = array() )
    {
        $response = $this->checkCategoryRedirect( $locationId );
        if ( $response instanceof Response )
        {
            return $response;
        }

        return $this->get( 'ez_content' )->viewLocation( $locationId, $viewType, $layout, $params );
    }

    /**
     * Checks if content at location defined by it's ID contains
     * valid category redirect value and returns a redirect response if it does
     *
     * @param mixed $locationId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function checkCategoryRedirect( $locationId )
    {
        $contentService = $this->getRepository()->getContentService();
        $location = $this->getRepository()->getLocationService()->loadLocation( $locationId );
        $content = $contentService->loadContent( $location->contentId );

        $fieldHelper = $this->container->get( 'ezpublish.field_helper' );

        if ( ( $internalRedirect = $content->getFieldValue( 'internal_redirect' ) ) instanceof RelationValue &&
             !$fieldHelper->isFieldEmpty( $content, 'internal_redirect' ) )
        {
            $internalRedirectContentInfo = $contentService->loadContentInfo( $internalRedirect->destinationContentId );
            if ( $internalRedirectContentInfo->mainLocationId != $locationId )
            {
                return new RedirectResponse(
                    $this->container->get( 'router' )->generate(
                        'ez_urlalias',
                        array(
                            'locationId' => $internalRedirectContentInfo->mainLocationId
                        )
                    ),
                    RedirectResponse::HTTP_MOVED_PERMANENTLY
                );
            }
        }
        else if ( ( $externalRedirect = $content->getFieldValue( 'external_redirect' ) ) instanceof UrlValue &&
            !$fieldHelper->isFieldEmpty( $content, 'external_redirect' ) )
        {
            if ( stripos( $externalRedirect->link, 'http' ) === 0 )
            {
                return new RedirectResponse( $externalRedirect->link, RedirectResponse::HTTP_MOVED_PERMANENTLY );
            }

            return new RedirectResponse(
                $this->container->get( 'router' )->generate(
                    'ez_urlalias',
                    array(
                        'locationId' => $this->getConfigResolver()->getParameter( 'content.tree_root.location_id' )
                    )
                ) . trim( $externalRedirect->link, '/' ),
                RedirectResponse::HTTP_MOVED_PERMANENTLY
            );
        }
    }
}
