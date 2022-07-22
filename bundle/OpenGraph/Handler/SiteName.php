<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\OpenGraph\Handler;

use Netgen\Bundle\OpenGraphBundle\Handler\HandlerInterface;
use Netgen\Bundle\OpenGraphBundle\MetaTag\Item;
use Netgen\Bundle\SiteBundle\Helper\SiteInfoHelper;

use function trim;

class SiteName implements HandlerInterface
{
    protected SiteInfoHelper $siteInfoHelper;

    public function __construct(SiteInfoHelper $siteInfoHelper)
    {
        $this->siteInfoHelper = $siteInfoHelper;
    }

    public function getMetaTags($tagName, array $params = []): array
    {
        $siteName = $this->siteInfoHelper->getSiteInfoContent()->getField('site_name')->value->text;

        return [
            new Item($tagName, trim($siteName)),
        ];
    }
}
