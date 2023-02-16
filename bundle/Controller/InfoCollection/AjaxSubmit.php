<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller\InfoCollection;

use Netgen\Bundle\IbexaSiteApiBundle\View\ContentRenderer;
use Netgen\Bundle\SiteBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

final class AjaxSubmit extends Controller
{
    public function __construct(private ContentRenderer $contentRenderer)
    {
    }

    public function __invoke(int $formContentId): Response
    {
        return new Response(
            $this->contentRenderer->renderContent(
                $this->getSite()->getLoadService()->loadContent($formContentId),
                'payload',
            ),
        );
    }
}
