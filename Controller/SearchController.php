<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Controller;

use Netgen\EzPlatformSiteApi\Core\Site\Pagination\Pagerfanta\FindAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends Controller
{
    /**
     * Action for displaying the results of full text search.
     */
    public function search(Request $request): Response
    {
        $configResolver = $this->getConfigResolver();
        $queryType = $this->getQueryTypeRegistry()->getQueryType('NetgenMore:Search');

        $searchText = trim($request->query->get('searchText', ''));

        if (empty($searchText)) {
            return $this->render(
                $configResolver->getParameter('template.search', 'ngmore'),
                [
                    'search_text' => '',
                    'pager' => null,
                ]
            );
        }

        $pager = new Pagerfanta(
            new FindAdapter(
                $queryType->getQuery(['search_text' => $searchText]),
                $this->getSite()->getFindService()
            )
        );

        $pager->setNormalizeOutOfRangePages(true);
        $pager->setMaxPerPage((int) $configResolver->getParameter('search.default_limit', 'ngmore'));

        $currentPage = $request->query->getInt('page', 1);
        $pager->setCurrentPage($currentPage > 0 ? $currentPage : 1);

        return $this->render(
            $configResolver->getParameter('template.search', 'ngmore'),
            [
                'search_text' => $searchText,
                'pager' => $pager,
            ]
        );
    }
}
