<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller\FullView;

use Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView;
use Netgen\Bundle\SiteBundle\Controller\Controller;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use Symfony\Component\HttpFoundation\Response;

class LandingPage extends Controller
{
    /**
     * Action for viewing content with ng_landing_page content type identifier.
     *
     * @return \Symfony\Component\HttpFoundation\Response|\Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView
     */
    public function __invoke(ContentView $view)
    {
        $location = $view->getSiteLocation();
        if (!$location instanceof Location) {
            $location = $view->getSiteContent()->mainLocation;
        }

        $response = $this->checkRedirect($location);
        if ($response instanceof Response) {
            return $response;
        }

        return $view;
    }
}
