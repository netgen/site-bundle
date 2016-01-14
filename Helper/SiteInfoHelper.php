<?php

namespace Netgen\Bundle\MoreBundle\Helper;

use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\Core\MVC\ConfigResolverInterface;

class SiteInfoHelper
{
    /**
     * @var \eZ\Publish\API\Repository\LocationService
     */
    protected $locationService;

    /**
     * @var \eZ\Publish\API\Repository\ContentService
     */
    protected $contentService;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var \eZ\Publish\API\Repository\Values\Content\Location
     */
    protected $siteInfoLocation;

    /**
     * @var \eZ\Publish\API\Repository\Values\Content\Content
     */
    protected $siteInfoContent;

    /**
     * Constructor.
     *
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     */
    public function __construct(
        LocationService $locationService,
        ContentService $contentService,
        ConfigResolverInterface $configResolver
    ) {
        $this->locationService = $locationService;
        $this->contentService = $contentService;
        $this->configResolver = $configResolver;
    }

    /**
     * Returns the SiteInfo location.
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Location
     */
    public function getSiteInfoLocation()
    {
        $siteInfoLocationId = $this->configResolver->hasParameter('locations.site_info.id', 'ngmore') ?
            $this->configResolver->getParameter('locations.site_info.id', 'ngmore') :
            $this->configResolver->getParameter('SpecialNodes.SiteInfoNode', 'ngmore');

        if ($this->siteInfoLocation === null) {
            $this->siteInfoLocation = $this->locationService->loadLocation($siteInfoLocationId);
        }

        return $this->siteInfoLocation;
    }

    /**
     * Returns the SiteInfo content.
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Content
     */
    public function getSiteInfoContent()
    {
        if ($this->siteInfoContent === null) {
            $siteInfoLocation = $this->getSiteInfoLocation();
            if ($siteInfoLocation !== null) {
                $this->siteInfoContent = $this->contentService->loadContent(
                    $siteInfoLocation->contentId
                );
            }
        }

        return $this->siteInfoContent;
    }
}
