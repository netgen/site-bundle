<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller\InfoCollection;

use Netgen\Bundle\SiteBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

final class AjaxSubmit extends Controller
{
    public function __invoke(int $formContentId): Response
    {
        return new Response(
            $this->getContentRenderer()->renderContent(
                $this->getLoadService()->loadContent($formContentId),
                'payload',
            ),
        );
    }
}
