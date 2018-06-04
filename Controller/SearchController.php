<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
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

        $searchText = trim($request->get('searchText', ''));
        $contentTypes = $configResolver->getParameter('search.content_types', 'ngmore');

        if (empty($searchText)) {
            return $this->render(
                $configResolver->getParameter('template.search', 'ngmore'),
                [
                    'search_text' => '',
                    'locations' => [],
                ]
            );
        }

        $criteria = [
            new Criterion\Subtree($this->getRootLocation()->pathString),
            new Criterion\Visibility(Criterion\Visibility::VISIBLE),
        ];

        if (is_array($contentTypes) && !empty($contentTypes)) {
            $criteria[] = new Criterion\ContentTypeIdentifier($contentTypes);
        }

        $query = new LocationQuery();
        $query->query = new Criterion\FullText($searchText);
        $query->filter = new Criterion\LogicalAnd($criteria);

        $pager = new Pagerfanta(
            new FindAdapter(
                $query,
                $this->getSite()->getFindService()
            )
        );

        $pager->setNormalizeOutOfRangePages(true);
        $pager->setMaxPerPage(
            (int) $configResolver->getParameter('search.default_limit', 'ngmore')
        );

        $currentPage = (int) $request->get('page', 1);
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
