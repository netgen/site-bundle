<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller;

use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\Core\FieldType\Url\Value as UrlValue;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\MVC\Symfony\Routing\UrlAliasRouter;
use Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView;
use Netgen\EzPlatformSiteApi\API\Site;
use Netgen\EzPlatformSiteApi\API\Values\Content;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use Netgen\EzPlatformSiteApi\Core\Site\Pagination\Pagerfanta\FilterAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FullViewController extends Controller
{
    /**
     * @var \Netgen\EzPlatformSiteApi\API\Site
     */
    protected $site;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    public function __construct(
        Site $site,
        ConfigResolverInterface $configResolver
    ) {
        $this->site = $site;
        $this->configResolver = $configResolver;
    }

    /**
     * Action for viewing content with ng_category content type identifier.
     *
     * @return \Symfony\Component\HttpFoundation\Response|\Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView
     */
    public function viewNgCategory(Request $request, ContentView $view, array $params = [])
    {
        $content = $view->getSiteContent();
        $location = $view->getSiteLocation();
        if (!$location instanceof Location) {
            $location = $content->mainLocation;
        }

        $response = $this->checkCategoryRedirect($location);
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

    /**
     * Action for viewing content with ng_landing_page content type identifier.
     *
     * @return \Symfony\Component\HttpFoundation\Response|\Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView
     */
    public function viewNgLandingPage(ContentView $view)
    {
        $location = $view->getSiteLocation();
        if (!$location instanceof Location) {
            $location = $view->getSiteContent()->mainLocation;
        }

        $response = $this->checkCategoryRedirect($location);
        if ($response instanceof Response) {
            return $response;
        }

        return $view;
    }

    /**
     * Checks if content at location defined by it's ID contains
     * valid category redirect value and returns a redirect response if it does.
     */
    protected function checkCategoryRedirect(Location $location): ?RedirectResponse
    {
        $content = $location->content;

        $internalRedirectContent = null;
        if (!$content->getField('internal_redirect')->isEmpty()) {
            $internalRedirectContent = $content->getFieldRelation('internal_redirect');
        }

        $externalRedirectValue = $content->getField('external_redirect')->value;

        if ($internalRedirectContent instanceof Content) {
            if ($internalRedirectContent->contentInfo->mainLocationId !== $location->id) {
                return new RedirectResponse(
                    $this->generateUrl(
                        UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
                        ['location' => $internalRedirectContent->mainLocation]
                    ),
                    RedirectResponse::HTTP_MOVED_PERMANENTLY
                );
            }
        } elseif ($externalRedirectValue instanceof UrlValue && !$content->getField('external_redirect')->isEmpty()) {
            if (mb_stripos($externalRedirectValue->link, 'http') === 0) {
                return new RedirectResponse($externalRedirectValue->link, RedirectResponse::HTTP_MOVED_PERMANENTLY);
            }

            $rootPath = $this->generateUrl(
                UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
                [
                    'locationId' => $this->getSite()->getSettings()->rootLocationId,
                ]
            );

            return new RedirectResponse(
                $rootPath . trim($externalRedirectValue->link, '/'),
                RedirectResponse::HTTP_MOVED_PERMANENTLY
            );
        }

        return null;
    }
}
