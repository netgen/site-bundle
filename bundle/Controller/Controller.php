<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller;

use eZ\Publish\Core\FieldType\Url\Value as UrlValue;
use eZ\Publish\Core\MVC\Symfony\Routing\UrlAliasRouter;
use Netgen\Bundle\EzPlatformSiteApiBundle\Controller\Controller as BaseController;
use Netgen\EzPlatformSiteApi\API\Values\Content;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function is_array;
use function mb_stripos;
use function trim;

abstract class Controller extends BaseController
{
    /**
     * Configures the response with provided cache settings.
     */
    protected function processCacheSettings(Request $request, Response $response): void
    {
        $defaultSharedMaxAge = $this->getConfigResolver()->getParameter('view.shared_max_age', 'ngsite');

        $cacheSettings = $request->attributes->get('cacheSettings');
        if (!is_array($cacheSettings)) {
            $cacheSettings = ['sharedMaxAge' => $defaultSharedMaxAge];
        }

        $public = true;

        if (isset($cacheSettings['sharedMaxAge'])) {
            $response->setSharedMaxAge($cacheSettings['sharedMaxAge']);
            if (empty($cacheSettings['sharedMaxAge'])) {
                $public = false;
            }
        } elseif (isset($cacheSettings['maxAge'])) {
            $response->setMaxAge($cacheSettings['maxAge']);
            if (empty($cacheSettings['maxAge'])) {
                $public = false;
            }
        } else {
            $response->setSharedMaxAge($defaultSharedMaxAge);
        }

        $public ? $response->setPublic() : $response->setPrivate();
    }

    /**
     * Checks if content at location defined by it's ID contains
     * valid category redirect value and returns a redirect response if it does.
     */
    protected function checkRedirect(Location $location): ?RedirectResponse
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
                        '',
                        [RouteObjectInterface::ROUTE_OBJECT => $internalRedirectContent]
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
