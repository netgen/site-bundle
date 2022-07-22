<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use Netgen\Bundle\SiteBundle\Core\Search\SuggestionResolver;
use Netgen\EzPlatformSiteApi\Core\Site\Pagination\Pagerfanta\FindAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function trim;

class SearchController extends Controller
{
    protected SuggestionResolver $suggestionResolver;

    public function __construct(SuggestionResolver $suggestionResolver)
    {
        $this->suggestionResolver = $suggestionResolver;
    }

    /**
     * Action for displaying the results of full text search.
     */
    public function search(Request $request): Response
    {
        $configResolver = $this->getConfigResolver();
        $queryType = $this->getQueryTypeRegistry()->getQueryType('NetgenSite:Search');

        $searchText = trim($request->query->get('searchText', ''));

        if (empty($searchText)) {
            return $this->render(
                $configResolver->getParameter('template.search', 'ngsite'),
                [
                    'search_text' => '',
                    'pager' => null,
                ],
            );
        }

        $query = $queryType->getQuery(['search_text' => $searchText]);

        $pager = new Pagerfanta(
            new FindAdapter(
                $query,
                $this->getSite()->getFindService(),
            ),
        );

        $pager->setNormalizeOutOfRangePages(true);
        $pager->setMaxPerPage((int) $configResolver->getParameter('search.default_limit', 'ngsite'));

        $currentPage = $request->query->getInt('page', 1);
        $pager->setCurrentPage($currentPage > 0 ? $currentPage : 1);

        try {
            $searchSuggestion = $this->suggestionResolver->getSuggestedSearchTerm($query, $pager->getAdapter()->getSuggestion());
        } catch (NotFoundException $e) {
            $searchSuggestion = null;
        }

        return $this->render(
            $configResolver->getParameter('template.search', 'ngsite'),
            [
                'search_text' => $searchText,
                'search_suggestion' => $searchSuggestion,
                'pager' => $pager,
            ],
        );
    }
}
