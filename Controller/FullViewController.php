<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use Netgen\Bundle\EzPlatformSiteApiBundle\Controller\Controller;
use Netgen\Bundle\MoreBundle\Helper\SortClauseHelper;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use eZ\Publish\Core\FieldType\Relation\Value as RelationValue;
use eZ\Publish\Core\FieldType\Url\Value as UrlValue;
use Netgen\EzPlatformSiteApi\Core\Site\Pagination\Pagerfanta\LocationSearchFilterAdapter;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use Symfony\Component\Routing\RouterInterface;
use Pagerfanta\Pagerfanta;

class FullViewController extends Controller
{
    /**
     * @var \Netgen\Bundle\MoreBundle\Helper\SortClauseHelper
     */
    protected $sortClauseHelper;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    protected $router;

    /**
     * Constructor.
     *
     * @param \Netgen\Bundle\MoreBundle\Helper\SortClauseHelper $sortClauseHelper
     * @param \Symfony\Component\Routing\RouterInterface $router
     */
    public function __construct(SortClauseHelper $sortClauseHelper, RouterInterface $router)
    {
        $this->sortClauseHelper = $sortClauseHelper;
        $this->router = $router;
    }

    /**
     * Action for viewing content with ng_category content type identifier.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView $view
     *
     * @return \Symfony\Component\HttpFoundation\Response|\Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView
     */
    public function viewNgCategory(Request $request, ContentView $view)
    {
        $content = $view->getSiteContent();
        $location = $view->getSiteLocation();
        if (!$location instanceof Location) {
            $location = $content->mainLocation;
        }

        $response = $this->checkCategoryRedirect($location);
        if ($response instanceof Response) {
            return $response;
        }

        $criteria = array(
            new Criterion\Subtree($location->pathString),
            new Criterion\Visibility(Criterion\Visibility::VISIBLE),
            new Criterion\LogicalNot(new Criterion\LocationId($location->id)),
        );

        if (!$content->getField('fetch_subtree')->value->bool) {
            $criteria[] = new Criterion\Location\Depth(Criterion\Operator::EQ, $location->depth + 1);
        }

        if (!$content->getField('children_class_filter_include')->isEmpty()) {
            $contentTypeFilter = $content->getField('children_class_filter_include')->value;
            $criteria[] = new Criterion\ContentTypeIdentifier(
                array_map(
                    'trim',
                    explode(',', $contentTypeFilter)
                )
            );
        }

        $query = new LocationQuery();
        $query->filter = new Criterion\LogicalAnd($criteria);

        $query->sortClauses = array(
            $this->sortClauseHelper->getSortClauseBySortField(
                $location->sortField,
                $location->sortOrder
            ),
        );

        $pager = new Pagerfanta(
            new LocationSearchFilterAdapter(
                $query,
                $this->getSite()->getFilterService()
            )
        );

        $pager->setNormalizeOutOfRangePages(true);

        /** @var \eZ\Publish\Core\FieldType\Integer\Value $pageLimitValue */
        $pageLimitValue = $content->getField('page_limit')->value;

        $defaultLimit = 12;
        if (isset($params['childrenLimit'])) {
            $childrenLimit = (int)$params['childrenLimit'];
            if ($childrenLimit > 0) {
                $defaultLimit = $childrenLimit;
            }
        }

        $pager->setMaxPerPage($pageLimitValue->value > 0 ? (int)$pageLimitValue->value : $defaultLimit);

        $currentPage = (int)$request->get('page', 1);
        $pager->setCurrentPage($currentPage > 0 ? $currentPage : 1);

        $view->addParameters(
            array(
                'pager' => $pager,
            )
        );

        return $view;
    }

    /**
     * Action for viewing content with ng_landing_page content type identifier.
     *
     * @param \Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView $view
     *
     * @return \Symfony\Component\HttpFoundation\Response|\Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView
     */
    public function viewNgLandingPage(ContentView $view)
    {
        $location = $view->getSiteLocation();
        if (!$location instanceof Location) {
            $location = $view->getSiteContent()->mainLocation;
        }

        $response = $this->checkCategoryRedirect($location);
        if ($response instanceof Response) {
            return $response;
        }

        return $view;
    }

    /**
     * Checks if content at location defined by it's ID contains
     * valid category redirect value and returns a redirect response if it does.
     *
     * @param \Netgen\EzPlatformSiteApi\API\Values\Location $location
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function checkCategoryRedirect(Location $location)
    {
        $content = $location->content;
        $loadService = $this->getSite()->getLoadService();

        $internalRedirectValue = $content->getField('internal_redirect')->value;
        $externalRedirectValue = $content->getField('external_redirect')->value;
        if ($internalRedirectValue instanceof RelationValue && !$content->getField('internal_redirect')->isEmpty()) {
            $internalRedirectContentInfo = $loadService->loadContent($internalRedirectValue->destinationContentId)->contentInfo;
            if ($internalRedirectContentInfo->mainLocationId != $location->id) {
                return new RedirectResponse(
                    $this->router->generate($internalRedirectContentInfo),
                    RedirectResponse::HTTP_MOVED_PERMANENTLY
                );
            }
        } elseif ($externalRedirectValue instanceof UrlValue && !$content->getField('external_redirect')->isEmpty()) {
            if (stripos($externalRedirectValue->link, 'http') === 0) {
                return new RedirectResponse($externalRedirectValue->link, RedirectResponse::HTTP_MOVED_PERMANENTLY);
            }

            return new RedirectResponse(
                $this->router->generate($this->getRootLocation()) . trim($externalRedirectValue->link, '/'),
                RedirectResponse::HTTP_MOVED_PERMANENTLY
            );
        }
    }
}
