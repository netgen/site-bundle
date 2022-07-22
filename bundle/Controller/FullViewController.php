<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller;

use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView;
use Netgen\Bundle\SiteBundle\Helper\RedirectHelper;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use Netgen\EzPlatformSiteApi\Core\Site\Pagination\Pagerfanta\FilterAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

use function array_map;
use function explode;

class FullViewController extends Controller
{
    protected RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Action for viewing content with ng_category content type identifier.
     *
     * @deprecated this controller is deprecated, please use SiteAPI query type
     * for loading children and CheckRedirect controller for checking the redirect
     *
     * @return \Symfony\Component\HttpFoundation\Response|\Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView
     */
    public function viewNgCategory(Request $request, ContentView $view, array $params = [])
    {
        $content = $view->getSiteContent();
        $location = $view->getSiteLocation();
        if (!$location instanceof Location) {
            $location = $content->mainLocation;
        }

        $redirectHelper = new RedirectHelper($this->router, $this->getSite());

        $response = $redirectHelper->checkRedirect($location);
        if ($response instanceof Response) {
            return $response;
        }

        $criteria = [
            new Criterion\Subtree($location->pathString),
            new Criterion\Visibility(Criterion\Visibility::VISIBLE),
            new Criterion\LogicalNot(new Criterion\LocationId($location->id)),
        ];

        if (!$content->getField('fetch_subtree')->value->bool) {
            $criteria[] = new Criterion\Location\Depth(Criterion\Operator::EQ, $location->depth + 1);
        }

        if (!$content->getField('children_class_filter_include')->isEmpty()) {
            $contentTypeFilter = $content->getField('children_class_filter_include')->value;
            $criteria[] = new Criterion\ContentTypeIdentifier(
                array_map(
                    'trim',
                    explode(',', $contentTypeFilter->text),
                ),
            );
        }

        $query = new LocationQuery();
        $query->filter = new Criterion\LogicalAnd($criteria);
        $query->sortClauses = $location->innerLocation->getSortClauses();

        $pager = new Pagerfanta(
            new FilterAdapter(
                $query,
                $this->getSite()->getFilterService(),
            ),
        );

        $pager->setNormalizeOutOfRangePages(true);

        /** @var \eZ\Publish\Core\FieldType\Integer\Value $pageLimitValue */
        $pageLimitValue = $content->getField('page_limit')->value;

        $defaultLimit = 12;

        $childrenLimit = (int) ($params['childrenLimit'] ?? $defaultLimit);
        $childrenLimit = $childrenLimit > 0 ? $childrenLimit : $defaultLimit;

        $pager->setMaxPerPage($pageLimitValue->value > 0 ? (int) $pageLimitValue->value : $childrenLimit);

        $currentPage = (int) $request->get('page', 1);
        $pager->setCurrentPage($currentPage > 0 ? $currentPage : 1);

        $view->addParameters([
            'pager' => $pager,
        ]);

        return $view;
    }

    /**
     * Action for viewing content with ng_landing_page content type identifier.
     *
     * @deprecated this controller is deprecated, please use CheckRedirect controller
     * for checking the redirect
     *
     * @return \Symfony\Component\HttpFoundation\Response|\Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView
     */
    public function viewNgLandingPage(ContentView $view)
    {
        $location = $view->getSiteLocation();
        if (!$location instanceof Location) {
            $location = $view->getSiteContent()->mainLocation;
        }

        $redirectHelper = new RedirectHelper($this->router, $this->getSite());

        $response = $redirectHelper->checkRedirect($location);
        if ($response instanceof Response) {
            return $response;
        }

        return $view;
    }
}
