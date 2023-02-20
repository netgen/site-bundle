<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller;

use Netgen\Bundle\IbexaSiteApiBundle\View\ContentView;
use Netgen\Bundle\SiteBundle\Helper\RedirectHelper;
use Netgen\IbexaSiteApi\API\Values\Location;
use Symfony\Component\HttpFoundation\Response;

final class CheckRedirect extends Controller
{
    public function __construct(
        private readonly RedirectHelper $redirectHelper,
    ) {
    }

    /**
     * Action for viewing content which has redirect fields.
     */
    public function __invoke(ContentView $view): Response|ContentView
    {
        $location = $view->getSiteLocation();
        if (!$location instanceof Location) {
            $location = $view->getSiteContent()->mainLocation;
        }

        $response = $this->redirectHelper->checkRedirect($location);
        if ($response instanceof Response) {
            return $response;
        }

        return $view;
    }
}
