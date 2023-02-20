<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller\InfoCollection;

use Netgen\Bundle\SiteBundle\Controller\Controller;
use Netgen\Bundle\SiteBundle\InfoCollection\RefererResolver;
use Symfony\Component\HttpFoundation\Response;

final class ViewModal extends Controller
{
    public function __construct(
        private readonly RefererResolver $refererResolver
    ) {
    }

    public function __invoke(int $formContentId, ?int $refererLocationId = null): Response
    {
        $response = new Response(
            $this->getContentRenderer()->renderContent(
                $this->getLoadService()->loadContent($formContentId),
                'modal',
                [
                    'referer' => $this->refererResolver->getReferer($refererLocationId),
                ],
            ),
        );

        $response->setSharedMaxAge(0);
        $response->setPrivate();

        return $response;
    }
}
