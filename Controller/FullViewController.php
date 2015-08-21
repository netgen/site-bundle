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
        $translationHelper = $this->container->get( 'ezpublish.translation_helper' );

        $criteria = array(
            new Criterion\Subtree( $location->pathString ),
            new Criterion\Visibility( Criterion\Visibility::VISIBLE ),
            new Criterion\LogicalNot( new Criterion\LocationId( $location->id ) )
        );

        $fetchSubtreeValue = $translationHelper->getTranslatedField( $content, 'fetch_subtree' )->value;
        if ( !$fetchSubtreeValue->bool )
        {
            $criteria[] = new Criterion\Location\Depth( Criterion\Operator::EQ, $location->depth + 1 );
        }

        if ( !$fieldHelper->isFieldEmpty( $content, 'children_class_filter_include' ) )
        {
            $contentTypeFilter = $translationHelper->getTranslatedField( $content, 'children_class_filter_include' )->value;
            $criteria[] = new Criterion\ContentTypeIdentifier(
                array_map(
                    'trim',
                    explode( ',', $contentTypeFilter )
                )
            );
        }
        else if ( $this->getConfigResolver()->hasParameter( 'ChildrenNodeList.ExcludedClasses', 'content' ) )
        {
            $excludedContentTypes = $this->getConfigResolver()->getParameter( 'ChildrenNodeList.ExcludedClasses', 'content' );
            if ( !empty( $excludedContentTypes ) )
            {
                $criteria[] = new Criterion\LogicalNot(
                    new Criterion\ContentTypeIdentifier( $excludedContentTypes )
                );
            }
        }

        $query = new LocationQuery();
        $query->filter = new Criterion\LogicalAnd( $criteria );

        $query->sortClauses = array(
            $this->container->get( 'ngmore.helper.sort_clause_helper' )->getSortClauseBySortField(
                $location->sortField,
                $location->sortOrder
            )
        );

        $pager = new Pagerfanta(
            new LocationSearchAdapter(
                $query,
                $this->getRepository()->getSearchService()
            )
        );

        $pager->setNormalizeOutOfRangePages( true );

        /** @var \eZ\Publish\Core\FieldType\Integer\Value $pageLimitValue */
        $pageLimitValue = $translationHelper->getTranslatedField( $content, 'page_limit' )->value;

        $defaultLimit = 12;
        if ( isset( $params['childrenLimit'] ) )
        {
            $childrenLimit = (int)$params['childrenLimit'];
            if ( $childrenLimit > 0 )
            {
                $defaultLimit = $childrenLimit;
            }
        }

        $pager->setMaxPerPage( $pageLimitValue->value > 0 ? (int)$pageLimitValue->value : $defaultLimit );

        $currentPage = (int)$request->get( 'page', 1 );
        $pager->setCurrentPage( $currentPage > 0 ? $currentPage : 1 );

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
        $translationHelper = $this->container->get( 'ezpublish.translation_helper' );

        $internalRedirectValue = $translationHelper->getTranslatedField( $content, 'internal_redirect' )->value;
        $externalRedirectValue = $translationHelper->getTranslatedField( $content, 'external_redirect' )->value;
        if ( $internalRedirectValue instanceof RelationValue && !$fieldHelper->isFieldEmpty( $content, 'internal_redirect' ) )
        {
            $internalRedirectContentInfo = $contentService->loadContentInfo( $internalRedirectValue->destinationContentId );
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
        else if ( $externalRedirectValue instanceof UrlValue && !$fieldHelper->isFieldEmpty( $content, 'external_redirect' ) )
        {
            if ( stripos( $externalRedirectValue->link, 'http' ) === 0 )
            {
                return new RedirectResponse( $externalRedirectValue->link, RedirectResponse::HTTP_MOVED_PERMANENTLY );
            }

            return new RedirectResponse(
                $this->container->get( 'router' )->generate(
                    'ez_urlalias',
                    array(
                        'locationId' => $this->getConfigResolver()->getParameter( 'content.tree_root.location_id' )
                    )
                ) . trim( $externalRedirectValue->link, '/' ),
                RedirectResponse::HTTP_MOVED_PERMANENTLY
            );
        }
    }
}
