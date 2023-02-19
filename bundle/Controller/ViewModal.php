<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller;

use Symfony\Component\HttpFoundation\Response;

final class ViewModal extends Controller
{
    public function __invoke(int $contentId): Response
    {
        return new Response(
            $this->getContentRenderer()->renderContent(
                $this->getLoadService()->loadContent($contentId),
                'modal',
            ),
        );
    }
}
