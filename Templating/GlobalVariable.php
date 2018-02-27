<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Templating;

use Netgen\Bundle\MoreBundle\Helper\SiteInfoHelper;
use Netgen\EzPlatformSiteApi\API\Values\Content;
use Netgen\EzPlatformSiteApi\API\Values\Location;

class GlobalVariable
{
    /**
     * @var \Netgen\Bundle\MoreBundle\Helper\SiteInfoHelper
     */
    protected $siteInfoHelper;

    public function __construct(SiteInfoHelper $siteInfoHelper)
    {
        $this->siteInfoHelper = $siteInfoHelper;
    }

    public function getSiteInfoLocation(): Location
    {
        return $this->siteInfoHelper->getSiteInfoLocation();
    }

    public function getSiteInfoContent(): Content
    {
        return $this->siteInfoHelper->getSiteInfoContent();
    }
}
