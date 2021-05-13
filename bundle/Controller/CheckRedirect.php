<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller;

use Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView;
use Netgen\Bundle\SiteBundle\Helper\RedirectHelper;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use Symfony\Component\HttpFoundation\Response;

class CheckRedirect extends Controller
{
    protected RedirectHelper $redirectHelper;

    public function __construct(RedirectHelper $redirectHelper)
    {
        $this->redirectHelper = $redirectHelper;
    }

    /**
     * Action for viewing content which has redirect fields.
     *
     * @return \Symfony\Component\HttpFoundation\Response|\Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView
     */
    public function __invoke(ContentView $view)
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
