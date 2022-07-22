<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Helper;

use eZ\Publish\Core\FieldType\Url\Value as UrlValue;
use Netgen\EzPlatformSiteApi\API\Site;
use Netgen\EzPlatformSiteApi\API\Values\Content;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

use function mb_stripos;
use function trim;

class RedirectHelper
{
    protected RouterInterface $router;

    protected Site $site;

    public function __construct(RouterInterface $router, Site $site)
    {
        $this->router = $router;
        $this->site = $site;
    }

    /**
     * Checks if content on give location has internal or external
     * redirect fields, and if those have a valid redirect value.
     */
    public function checkRedirect(Location $location): ?Response
    {
        $content = $location->content;

        $internalRedirectContent = null;
        if ($content->hasField('internal_redirect') && !$content->getField('internal_redirect')->isEmpty()) {
            $internalRedirectContent = $content->getFieldRelation('internal_redirect');
        }

        $externalRedirectValue = $content->hasField('external_redirect')
            ? $content->getField('external_redirect')->value
            : null;

        if ($internalRedirectContent instanceof Content) {
            if ($internalRedirectContent->contentInfo->mainLocationId !== $location->id) {
                return new RedirectResponse(
                    $this->router->generate($internalRedirectContent),
                    RedirectResponse::HTTP_MOVED_PERMANENTLY,
                );
            }
        } elseif ($externalRedirectValue instanceof UrlValue && !$content->getField('external_redirect')->isEmpty()) {
            if (mb_stripos($externalRedirectValue->link, 'http') === 0) {
                return new RedirectResponse($externalRedirectValue->link, RedirectResponse::HTTP_MOVED_PERMANENTLY);
            }

            return new RedirectResponse(
                $this->router->generate($this->getRootLocation()) . trim($externalRedirectValue->link, '/'),
                RedirectResponse::HTTP_MOVED_PERMANENTLY,
            );
        }

        return null;
    }

    /**
     * Returns the root location object for current siteaccess configuration.
     */
    protected function getRootLocation(): Location
    {
        return $this->site->getLoadService()->loadLocation(
            $this->site->getSettings()->rootLocationId,
        );
    }
}
