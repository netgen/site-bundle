<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Templating;

use Ibexa\Contracts\Core\Repository\PermissionService;
use Netgen\Bundle\IbexaSiteApiBundle\NamedObject\Provider;
use Netgen\IbexaSiteApi\API\LoadService;
use Netgen\IbexaSiteApi\API\Values\Content;
use Netgen\IbexaSiteApi\API\Values\Location;

final class GlobalVariable
{
    public function __construct(
        private Provider $namedObjectProvider,
        private PermissionService $permissionService,
        private LoadService $loadService,
    ) {
    }

    public function getSiteInfoLocation(): Location
    {
        return $this->namedObjectProvider->getLocation('site_info');
    }

    public function getSiteInfoContent(): Content
    {
        return $this->getSiteInfoLocation()->content;
    }

    public function getCurrentUserContent(): Content
    {
        $currentUser = $this->permissionService->getCurrentUserReference();

        return $this->loadService->loadContent($currentUser->getUserId());
    }
}
