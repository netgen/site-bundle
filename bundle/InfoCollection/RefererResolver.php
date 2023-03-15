<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\InfoCollection;

use Ibexa\Core\MVC\Symfony\Routing\UrlAliasRouter;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RefererResolver
{
    public function __construct(private RequestStack $requestStack, private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function getReferer(?int $refererLocationId = null): string
    {
        if ($refererLocationId !== null) {
            return $this->urlGenerator->generate(
                UrlAliasRouter::URL_ALIAS_ROUTE_NAME,
                [
                    'locationId' => $refererLocationId,
                ],
                UrlGeneratorInterface::ABSOLUTE_URL,
            );
        }

        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            throw new RuntimeException('Missing request');
        }

        if ($request->headers->has('referer')) {
            return $request->headers->get('referer', '');
        }

        return $request->getPathInfo();
    }
}
