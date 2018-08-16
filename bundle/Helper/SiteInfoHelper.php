<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Helper;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Netgen\EzPlatformSiteApi\API\Values\Content;
use Netgen\EzPlatformSiteApi\API\Values\Location;

class SiteInfoHelper
{
    /**
     * @var \Netgen\EzPlatformSiteApi\API\LoadService
     */
    protected $loadService;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var \Netgen\EzPlatformSiteApi\API\Values\Location
     */
    protected $siteInfoLocation;

    /**
     * @var \Netgen\EzPlatformSiteApi\API\Values\Content
     */
    protected $siteInfoContent;

    public function __construct(
        LoadService $loadService,
        ConfigResolverInterface $configResolver
    ) {
        $this->loadService = $loadService;
        $this->configResolver = $configResolver;
    }

    public function getSiteInfoLocation(): Location
    {
        if ($this->siteInfoLocation === null) {
            $this->siteInfoLocation = $this->loadService->loadLocation(
                $this->configResolver->getParameter('locations.site_info.id', 'ngmore')
            );
        }

        return $this->siteInfoLocation;
    }

    public function getSiteInfoContent(): Content
    {
        if ($this->siteInfoContent === null) {
            $siteInfoLocation = $this->getSiteInfoLocation();
            if ($siteInfoLocation !== null) {
                $this->siteInfoContent = $siteInfoLocation->content;
            }
        }

        return $this->siteInfoContent;
    }
}
