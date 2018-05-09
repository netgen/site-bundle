<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\OpenGraph\Handler;

use Netgen\Bundle\MoreBundle\Helper\SiteInfoHelper;
use Netgen\Bundle\OpenGraphBundle\Handler\HandlerInterface;
use Netgen\Bundle\OpenGraphBundle\MetaTag\Item;

class SiteName implements HandlerInterface
{
    /**
     * @var \Netgen\Bundle\MoreBundle\Helper\SiteInfoHelper
     */
    protected $siteInfoHelper;

    public function __construct(SiteInfoHelper $siteInfoHelper)
    {
        $this->siteInfoHelper = $siteInfoHelper;
    }

    public function getMetaTags($tagName, array $params = array()): array
    {
        $siteName = $this->siteInfoHelper->getSiteInfoContent()->getField('site_name')->value->text;

        return array(
            new Item($tagName, trim($siteName)),
        );
    }
}
