<?php

namespace Netgen\Bundle\MoreBundle\Helper;

use Netgen\EzPlatformSite\API\LoadService;
use eZ\Publish\Core\MVC\ConfigResolverInterface;

class SiteInfoHelper
{
    /**
     * @var \Netgen\EzPlatformSite\API\LoadService
     */
    protected $loadService;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var \Netgen\EzPlatformSite\API\Values\Location
     */
    protected $siteInfoLocation;

    /**
     * @var \Netgen\EzPlatformSite\API\Values\Content
     */
    protected $siteInfoContent;

    /**
     * Constructor.
     *
     * @param \Netgen\EzPlatformSite\API\LoadService $loadService
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     */
    public function __construct(
        LoadService $loadService,
        ConfigResolverInterface $configResolver
    ) {
        $this->loadService = $loadService;
        $this->configResolver = $configResolver;
    }

    /**
     * Returns the SiteInfo location.
     *
     * @return \Netgen\EzPlatformSite\API\Values\Location
     */
    public function getSiteInfoLocation()
    {
        if ($this->siteInfoLocation === null) {
            $this->siteInfoLocation = $this->loadService->loadLocation(
                $this->configResolver->getParameter('locations.site_info.id', 'ngmore')
            );
        }

        return $this->siteInfoLocation;
    }

    /**
     * Returns the SiteInfo content.
     *
     * @return \Netgen\EzPlatformSite\API\Values\Content
     */
    public function getSiteInfoContent()
    {
        if ($this->siteInfoContent === null) {
            $siteInfoLocation = $this->getSiteInfoLocation();
            if ($siteInfoLocation !== null) {
                $this->siteInfoContent = $this->loadService->loadContent(
                    $siteInfoLocation->contentId
                );
            }
        }

        return $this->siteInfoContent;
    }
}
