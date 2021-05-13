<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Helper;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Netgen\EzPlatformSiteApi\API\Values\Content;
use Netgen\EzPlatformSiteApi\API\Values\Location;

class SiteInfoHelper
{
    protected LoadService $loadService;

    protected ConfigResolverInterface $configResolver;

    protected Location $siteInfoLocation;

    protected Content $siteInfoContent;

    public function __construct(
        LoadService $loadService,
        ConfigResolverInterface $configResolver
    ) {
        $this->loadService = $loadService;
        $this->configResolver = $configResolver;
    }

    public function getSiteInfoLocation(): Location
    {
        $this->siteInfoLocation ??= $this->loadService->loadLocation(
            $this->configResolver->getParameter('locations.site_info.id', 'ngsite'),
        );

        return $this->siteInfoLocation;
    }

    public function getSiteInfoContent(): Content
    {
        $this->siteInfoContent ??= $this->getSiteInfoLocation()->content;

        return $this->siteInfoContent;
    }
}
