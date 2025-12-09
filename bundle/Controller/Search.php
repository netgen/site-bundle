<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Netgen\Bundle\SiteBundle\Core\Search\SuggestionResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function mb_trim;

final class Search extends Controller
{
    public function __construct(
        private SuggestionResolver $suggestionResolver,
    ) {}

    /**
     * Action for displaying the results of full text search.
     */
    public function __invoke(Request $request): Response
    {
        $searchText = mb_trim($request->query->get('searchText', ''));
        $template = $this->getConfigResolver()->getParameter('template.search', 'ngsite');

        if ($searchText === '') {
            return $this->render(
                $template,
                [
                    'search_text' => '',
                    'pager' => null,
                ],
            );
        }

        $queryType = $this->getQueryTypeRegistry()->getQueryType('NetgenSite:Search');
        $query = $queryType->getQuery(['search_text' => $searchText]);
        $currentPage = $request->query->getInt('page', 1);
        $currentPage = $currentPage > 0 ? $currentPage : 1;
        $maxPerPage = (int) $this->getConfigResolver()->getParameter('search.default_limit', 'ngsite');

        $pager = $this->getFindPager($query, $currentPage, $maxPerPage);

        /** @var \Netgen\IbexaSearchExtra\Core\Pagination\Pagerfanta\SearchAdapter $searchAdapter */
        $searchAdapter = $pager->getAdapter();

        try {
            $searchSuggestion = $this->suggestionResolver->getSuggestedSearchTerm(
                $query,
                $searchAdapter->getSuggestion(),
            );
        } catch (NotFoundException) {
            $searchSuggestion = null;
        }

        return $this->render(
            $template,
            [
                'search_text' => $searchText,
                'search_suggestion' => $searchSuggestion,
                'pager' => $pager,
            ],
        );
    }
}
