<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller\FullView;

use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView;
use Netgen\Bundle\SiteBundle\Controller\Controller;
use Netgen\EzPlatformSiteApi\API\Site;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use Netgen\EzPlatformSiteApi\Core\Site\Pagination\Pagerfanta\FilterAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function array_map;
use function explode;

class Category extends Controller
{
    /**
     * @var \Netgen\EzPlatformSiteApi\API\Site
     */
    protected $site;

    public function __construct(Site $site)
    {
        $this->site = $site;
    }

    /**
     * Action for viewing content with ng_category content type identifier.
     *
     * @return \Symfony\Component\HttpFoundation\Response|\Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView
     */
    public function __invoke(Request $request, ContentView $view, array $params = [])
    {
        $content = $view->getSiteContent();
        $location = $view->getSiteLocation();
        if (!$location instanceof Location) {
            $location = $content->mainLocation;
        }

        $response = $this->checkRedirect($location);
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
                    explode(',', $contentTypeFilter->text)
                )
            );
        }

        $query = new LocationQuery();
        $query->filter = new Criterion\LogicalAnd($criteria);
        $query->sortClauses = $location->innerLocation->getSortClauses();

        $pager = new Pagerfanta(
            new FilterAdapter(
                $query,
                $this->site->getFilterService()
            )
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
}
