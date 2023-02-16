<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Helper;

use Ibexa\Core\FieldType\Url\Value as UrlValue;
use Netgen\IbexaSiteApi\API\Site;
use Netgen\IbexaSiteApi\API\Values\Content;
use Netgen\IbexaSiteApi\API\Values\Location;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function mb_stripos;
use function sprintf;
use function trim;

final class RedirectHelper
{
    public function __construct(private UrlGeneratorInterface $urlGenerator, private Site $site)
    {
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
                    $this->urlGenerator->generate('', [RouteObjectInterface::ROUTE_OBJECT => $internalRedirectContent]),
                    Response::HTTP_MOVED_PERMANENTLY,
                );
            }
        } elseif ($externalRedirectValue instanceof UrlValue && !$content->getField('external_redirect')->isEmpty()) {
            if (mb_stripos($externalRedirectValue->link, 'http') === 0) {
                return new RedirectResponse($externalRedirectValue->link, Response::HTTP_MOVED_PERMANENTLY);
            }

            return new RedirectResponse(
                sprintf(
                    '%s%s',
                    $this->urlGenerator->generate('', [RouteObjectInterface::ROUTE_OBJECT => $this->getRootLocation()]),
                    trim($externalRedirectValue->link, '/'),
                ),
                Response::HTTP_MOVED_PERMANENTLY,
            );
        }

        return null;
    }

    /**
     * Returns the root location object for current siteaccess configuration.
     */
    private function getRootLocation(): Location
    {
        return $this->site->getLoadService()->loadLocation(
            $this->site->getSettings()->rootLocationId,
        );
    }
}
