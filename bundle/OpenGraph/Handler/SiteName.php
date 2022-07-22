<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\OpenGraph\Handler;

use Netgen\Bundle\IbexaSiteApiBundle\NamedObject\Provider;
use Netgen\Bundle\OpenGraphBundle\Handler\HandlerInterface;
use Netgen\Bundle\OpenGraphBundle\MetaTag\Item;

use function trim;

class SiteName implements HandlerInterface
{
    protected Provider $namedObjectProvider;

    public function __construct(Provider $namedObjectProvider)
    {
        $this->namedObjectProvider = $namedObjectProvider;
    }

    public function getMetaTags(string $tagName, array $params = []): array
    {
        $siteName = $this->namedObjectProvider
            ->getLocation('site_info')
            ->content
            ->getField('site_name')
            ->value
            ->text;

        return [
            new Item($tagName, trim($siteName)),
        ];
    }
}
