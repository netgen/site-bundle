<?php

namespace Netgen\Bundle\MoreBundle\Pagerfanta\View;

use Pagerfanta\PagerfantaInterface;
use Pagerfanta\View\ViewInterface;
use Twig_Environment;

class NetgenMoreView implements ViewInterface
{
    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var string
     */
    protected $template;

    protected $pagerfanta;
    protected $proximity;

    protected $currentPage;
    protected $nbPages;

    protected $startPage;
    protected $endPage;

    /**
     * Constructor.
     *
     * @param \Twig_Environment $twig
     */
    public function __construct(Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Sets the default template
     *
     * @param string $template
     */
    public function setDefaultTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * Returns the canonical name.
     *
     * @return string The canonical name.
     */
    public function getName()
    {
        return 'ngmore';
    }

    /**
     * Renders a pagerfanta.
     *
     * The route generator can be any callable to generate
     * the routes receiving the page number as first and
     * unique argument.
     *
     * @param \Pagerfanta\PagerfantaInterface $pagerfanta A pagerfanta.
     * @param \Closure $routeGenerator A callable to generate the routes.
     * @param array $options An array of options (optional).
     *
     * @return string
     */
    public function render(PagerfantaInterface $pagerfanta, $routeGenerator, array $options = array())
    {
        /** @var \Pagerfanta\Pagerfanta $pagerfanta */

        $this->initializeProximity($options);
        $this->calculateStartAndEndPage($pagerfanta);

        return $this->twig->render(
            isset($options['template']) ? $options['template'] : $this->template,
            array(
                'pager' => $pagerfanta,
                'pages' => $this->getPages($pagerfanta, $routeGenerator)
            )
        );
    }

    protected function initializeProximity($options)
    {
        $this->proximity = isset($options['proximity']) ?
            (int)$options['proximity'] :
            2;
    }

    protected function calculateStartAndEndPage(PagerfantaInterface $pagerfanta)
    {
        $currentPage = $pagerfanta->getCurrentPage();
        $nbPages = $pagerfanta->getNbPages();

        $startPage = $currentPage - $this->proximity;
        $endPage = $currentPage + $this->proximity;

        if ($startPage < 1) {
            $endPage = $this->calculateEndPageForStartPageUnderflow($startPage, $endPage, $nbPages);
            $startPage = 1;
        }

        if ($endPage > $nbPages) {
            $startPage = $this->calculateStartPageForEndPageOverflow($startPage, $endPage, $nbPages);
            $endPage = $nbPages;
        }

        $this->startPage = $startPage;
        $this->endPage = $endPage;
    }

    protected function calculateEndPageForStartPageUnderflow($startPage, $endPage, $nbPages)
    {
        return min($endPage + (1 - $startPage), $nbPages);
    }

    protected function calculateStartPageForEndPageOverflow($startPage, $endPage, $nbPages)
    {
        return max($startPage - ($endPage - $nbPages), 1);
    }

    protected function getPages(PagerfantaInterface $pagerfanta, $routeGenerator)
    {
        $pages = array();

        $pages['previous_page'] = $pagerfanta->hasPreviousPage() ?
            $routeGenerator($pagerfanta->getPreviousPage()) :
            false;

        // We use trim here because Pagerfanta (or Symfony?) adds an extra '?'
        // at the end of first page when there are no other query params
        $pages['first_page'] = $this->startPage > 1 ? trim($routeGenerator(1), '?') : false;

        $pages['second_page'] = $this->startPage == 3 ? $routeGenerator(2) : false;

        $pages['separator_before'] = $this->startPage > 3 ? true : false;

        $middlePages = array();
        for ($i = $this->startPage, $end = $this->endPage; $i <= $end; $i++) {
            $middlePages[$i] = $routeGenerator($i);
        }

        $pages['middle_pages'] = $middlePages;

        $pages['separator_after'] = $this->endPage < $pagerfanta->getNbPages() - 2 ? true : false;

        $pages['second_to_last_page'] = $this->endPage == $pagerfanta->getNbPages() - 2 ?
            $routeGenerator($pagerfanta->getNbPages() - 1) :
            false;

        $pages['last_page'] = $pagerfanta->getNbPages() > $this->endPage ?
            $routeGenerator($pagerfanta->getNbPages()) :
            false;

        $pages['next_page'] = $pagerfanta->hasNextPage() ? $routeGenerator($pagerfanta->getNextPage()) : false;

        return $pages;
    }
}
