<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\InfoCollection;

use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RefererResolver
{
    private RequestStack $requestStack;

    private UrlGeneratorInterface $urlGenerator;

    public function __construct(RequestStack $requestStack, UrlGeneratorInterface $urlGenerator)
    {
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
    }

    public function getReferer(?int $refererLocationId = null): string
    {
        if ($refererLocationId !== null) {
            return $this->urlGenerator->generate(
                'ibexa.url.alias',
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
            return $request->headers->get('referer');
        }

        return $request->getPathInfo();
    }
}
