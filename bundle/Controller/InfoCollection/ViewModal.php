<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller\InfoCollection;

use Netgen\Bundle\IbexaSiteApiBundle\View\ContentRenderer;
use Netgen\Bundle\SiteBundle\Controller\Controller;
use Netgen\Bundle\SiteBundle\InfoCollection\RefererResolver;
use Symfony\Component\HttpFoundation\Response;

class ViewModal extends Controller
{
    private ContentRenderer $contentRenderer;

    private RefererResolver $refererResolver;

    public function __construct(ContentRenderer $contentRenderer, RefererResolver $refererResolver)
    {
        $this->contentRenderer = $contentRenderer;
        $this->refererResolver = $refererResolver;
    }

    public function __invoke(int $formContentId, ?int $refererLocationId = null): Response
    {
        $response = new Response(
            $this->contentRenderer->renderContent(
                $this->getSite()->getLoadService()->loadContent($formContentId),
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
