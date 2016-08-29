<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use Netgen\Bundle\EzPlatformSiteApiBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Netgen\EzPlatformSite\Core\Site\Pagination\Pagerfanta\LocationSearchAdapter;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use Pagerfanta\Pagerfanta;

class SearchController extends Controller
{
    /**
     * @var \Netgen\EzPlatformSite\API\FindService
     */
    protected $findService;

    /**
     * @var \Netgen\EzPlatformSite\API\LoadService
     */
    protected $loadService;

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
     */
    public function __construct()
    {
        $this->findService = $this->getSite()->getFindService();
        $this->loadService = $this->getSite()->getLoadService();

        $this->defaultLimit = (int)$this->getConfigResolver()->getParameter('search.default_limit', 'ngmore');
        $this->template = $this->getConfigResolver()->getParameter('template.search', 'ngmore');
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

        $criteria = array(
            new Criterion\FullText($searchText),
            new Criterion\Subtree($this->getRootLocation()->pathString),
            new Criterion\Visibility(Criterion\Visibility::VISIBLE),
        );

        $query = new LocationQuery();
        $query->query = new Criterion\LogicalAnd($criteria);

        $pager = new Pagerfanta(
            new LocationSearchAdapter(
                $query,
                $this->findService
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
