<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\QueryType\QueryTypeRegistry;
use Netgen\EzPlatformSiteApi\API\Site;
use Netgen\EzPlatformSiteApi\Core\Site\Pagination\Pagerfanta\FindAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function trim;

class SearchController extends Controller
{
    /**
     * @var \Netgen\EzPlatformSiteApi\API\Site
     */
    protected $site;

    /**
     * @var \eZ\Publish\Core\QueryType\QueryTypeRegistry
     */
    protected $queryTypeRegistry;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    public function __construct(Site $site, QueryTypeRegistry $queryTypeRegistry, ConfigResolverInterface $configResolver)
    {
        $this->site = $site;
        $this->queryTypeRegistry = $queryTypeRegistry;
        $this->configResolver = $configResolver;
    }

    /**
     * Action for displaying the results of full text search.
     */
    public function search(Request $request): Response
    {
        $queryType = $this->queryTypeRegistry->getQueryType('NetgenSite:Search');

        $searchText = trim($request->query->get('searchText', ''));

        if (empty($searchText)) {
            return $this->render(
                $this->configResolver->getParameter('template.search', 'ngsite'),
                [
                    'search_text' => '',
                    'pager' => null,
                ]
            );
        }

        $pager = new Pagerfanta(
            new FindAdapter(
                $queryType->getQuery(['search_text' => $searchText]),
                $this->site->getFindService()
            )
        );

        $pager->setNormalizeOutOfRangePages(true);
        $pager->setMaxPerPage((int) $this->configResolver->getParameter('search.default_limit', 'ngsite'));

        $currentPage = $request->query->getInt('page', 1);
        $pager->setCurrentPage($currentPage > 0 ? $currentPage : 1);

        return $this->render(
            $this->configResolver->getParameter('template.search', 'ngsite'),
            [
                'search_text' => $searchText,
                'pager' => $pager,
            ]
        );
    }
}
