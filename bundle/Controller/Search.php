<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\QueryType\QueryTypeRegistry;
use Netgen\Bundle\SiteBundle\Core\Search\SuggestionResolver;
use Netgen\IbexaSiteApi\API\Site;
use Netgen\IbexaSiteApi\Core\Site\Pagination\Pagerfanta\FindAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function trim;

class Search extends Controller
{
    protected Site $site;

    protected QueryTypeRegistry $queryTypeRegistry;

    protected ConfigResolverInterface $configResolver;

    protected SuggestionResolver $suggestionResolver;

    public function __construct(
        Site $site,
        QueryTypeRegistry $queryTypeRegistry,
        ConfigResolverInterface $configResolver,
        SuggestionResolver $suggestionResolver
    ) {
        $this->site = $site;
        $this->queryTypeRegistry = $queryTypeRegistry;
        $this->configResolver = $configResolver;
        $this->suggestionResolver = $suggestionResolver;
    }

    /**
     * Action for displaying the results of full text search.
     */
    public function __invoke(Request $request): Response
    {
        $queryType = $this->queryTypeRegistry->getQueryType('NetgenSite:Search');

        $searchText = trim($request->query->get('searchText', ''));

        if (empty($searchText)) {
            return $this->render(
                $this->configResolver->getParameter('template.search', 'ngsite'),
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
                $this->site->getFindService(),
            ),
        );

        $pager->setNormalizeOutOfRangePages(true);
        $pager->setMaxPerPage((int) $this->configResolver->getParameter('search.default_limit', 'ngsite'));

        $currentPage = $request->query->getInt('page', 1);
        $pager->setCurrentPage($currentPage > 0 ? $currentPage : 1);

        try {
            $searchSuggestion = $this->suggestionResolver->getSuggestedSearchTerm($query, $pager->getAdapter()->getSuggestion());
        } catch (NotFoundException $e) {
            $searchSuggestion = null;
        }

        return $this->render(
            $this->configResolver->getParameter('template.search', 'ngsite'),
            [
                'search_text' => $searchText,
                'search_suggestion' => $searchSuggestion,
                'pager' => $pager,
            ],
        );
    }
}
