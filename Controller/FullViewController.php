<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use Symfony\Component\HttpFoundation\Request;
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
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param mixed $locationId
     * @param string $viewType
     * @param boolean $layout
     * @param array $params
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewNgCategoryLocation( Request $request, $locationId, $viewType, $layout = false, array $params = array() )
    {
        $response = $this->checkCategoryRedirect( $locationId );
        if ( $response instanceof Response )
        {
            return $response;
        }

        $location = $this->getRepository()->getLocationService()->loadLocation( $locationId );
        $content = $this->getRepository()->getContentService()->loadContent( $location->contentId );
        $fieldHelper = $this->container->get( 'ezpublish.field_helper' );

        $criterions = array(
            new Criterion\Subtree( $location->pathString ),
            new Criterion\Visibility( Criterion\Visibility::VISIBLE ),
            new Criterion\LogicalNot( new Criterion\LocationId( $location->id ) ),

        );

        if ( !$content->getFieldValue( 'fetch_subtree' )->bool )
        {
            $criterions[] = new Criterion\Location\Depth( Criterion\Operator::EQ, $location->depth + 1 );
        }

        if ( !$fieldHelper->isFieldEmpty( $content, 'children_class_filter_include' ) )
        {
            $criterions[] = new Criterion\ContentTypeIdentifier(
                array_map(
                    'trim',
                    explode( ',', $content->getFieldValue( 'children_class_filter_include' ) )
                )
            );
        }
        else if ( $this->getConfigResolver()->hasParameter( 'ChildrenNodeList.ExcludedClasses', 'content' ) )
        {
            $criterions[] = new Criterion\LogicalNot(
                new Criterion\ContentTypeIdentifier(
                    $this->getConfigResolver()->getParameter( 'ChildrenNodeList.ExcludedClasses', 'content' )
                )
            );
        }

        $query = new LocationQuery();
        $query->criterion = new Criterion\LogicalAnd( $criterions );

        $pager = new Pagerfanta(
            new LocationSearchAdapter(
                $query,
                $this->getRepository()->getSearchService()
            )
        );

        /** @var \eZ\Publish\Core\FieldType\Integer\Value $pageLimitValue */
        $pageLimitValue = $content->getFieldValue( 'page_limit' );

        $pager->setMaxPerPage( $pageLimitValue->value > 0 ? $pageLimitValue->value : 12 );
        $pager->setCurrentPage( $request->get( 'page', 1 ) );

        $query->sortClauses = array(
            $this->container->get( 'netgen_more.helper.sort_clause_helper' )->getSortClauseBySortField(
                $location->sortField,
                $location->sortOrder
            )
        );

        return $this->get( 'ez_content' )->viewLocation(
            $locationId,
            $viewType,
            $layout,
            $params + array(
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