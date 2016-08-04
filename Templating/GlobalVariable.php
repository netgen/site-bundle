<?php

namespace Netgen\Bundle\MoreBundle\Templating;

use Netgen\Bundle\MoreBundle\Helper\SiteInfoHelper;

class GlobalVariable
{
    /**
     * @var \Netgen\Bundle\MoreBundle\Helper\SiteInfoHelper
     */
    protected $siteInfoHelper;

    /**
     * Constructor.
     *
     * @param \Netgen\Bundle\MoreBundle\Helper\SiteInfoHelper $siteInfoHelper
     */
    public function __construct(SiteInfoHelper $siteInfoHelper)
    {
        $this->siteInfoHelper = $siteInfoHelper;
    }

    /**
     * Returns the SiteInfo location.
     *
     * @return \Netgen\EzPlatformSiteApi\API\Values\Location
     */
    public function getSiteInfoLocation()
    {
        return $this->siteInfoHelper->getSiteInfoLocation();
    }

    /**
     * Returns the SiteInfo content.
     *
     * @return \Netgen\EzPlatformSiteApi\API\Values\Content
     */
    public function getSiteInfoContent()
    {
        return $this->siteInfoHelper->getSiteInfoContent();
    }
}
