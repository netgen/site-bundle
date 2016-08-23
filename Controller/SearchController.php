<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use eZ\Publish\Core\Pagination\Pagerfanta\LocationSearchAdapter;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use Pagerfanta\Pagerfanta;

class SearchController extends Controller
{
    /**
     * @var \eZ\Publish\API\Repository\SearchService
     */
    protected $searchService;

    /**
     * @var \eZ\Publish\API\Repository\LocationService
     */
    protected $locationService;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var int
     */
    protected $defaultLimit;

    /**
     * @var string
     */
    protected $template;

    /**
     * Constructor.
     *
     * @param \eZ\Publish\API\Repository\SearchService $searchService
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     */
    public function __construct(
        SearchService $searchService,
        LocationService $locationService,
        ConfigResolverInterface $configResolver
    ) {
        $this->searchService = $searchService;
        $this->locationService = $locationService;
        $this->configResolver = $configResolver;

        $this->defaultLimit = (int)$this->configResolver->getParameter('search.default_limit', 'ngmore');
        $this->template = $this->configResolver->getParameter('template.search', 'ngmore');
    }

    /**
     * Action for displaying the results of full text search.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function search(Request $request)
    {
        $searchText = $request->get('searchText', '');

        if (empty($searchText)) {
            return $this->render(
                $this->template,
                array(
                    'search_text' => '',
                    'locations' => array(),
                )
            );
        }

        $rootLocationId = $this->configResolver->getParameter('content.tree_root.location_id');
        $rootLocation = $this->locationService->loadLocation($rootLocationId);

        $criteria = array(
            new Criterion\FullText($searchText),
            new Criterion\Subtree($rootLocation->pathString),
            new Criterion\Visibility(Criterion\Visibility::VISIBLE),
        );

        $query = new LocationQuery();
        $query->query = new Criterion\LogicalAnd($criteria);

        $pager = new Pagerfanta(
            new LocationSearchAdapter(
                $query,
                $this->searchService,
                array(
                    'languages' => $this->configResolver->getParameter('languages'),
                )
            )
        );

        $pager->setNormalizeOutOfRangePages(true);
        $pager->setMaxPerPage($this->defaultLimit);

        $currentPage = (int)$request->get('page', 1);
        $pager->setCurrentPage($currentPage > 0 ? $currentPage : 1);

        return $this->render(
            $this->template,
            array(
                'search_text' => $searchText,
                'locations' => $pager,
            )
        );
    }
}
