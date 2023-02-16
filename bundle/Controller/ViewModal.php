<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller;

use Netgen\Bundle\IbexaSiteApiBundle\View\ContentRenderer;
use Symfony\Component\HttpFoundation\Response;

final class ViewModal extends Controller
{
    public function __construct(private ContentRenderer $contentRenderer)
    {
    }

    public function __invoke(int $contentId): Response
    {
        return new Response(
            $this->contentRenderer->renderContent(
                $this->getSite()->getLoadService()->loadContent($contentId),
                'modal',
            ),
        );
    }
}
