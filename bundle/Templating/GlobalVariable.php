<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Templating;

use Ibexa\Contracts\Core\Repository\PermissionService;
use Netgen\Bundle\IbexaSiteApiBundle\NamedObject\Provider;
use Netgen\IbexaSiteApi\API\LoadService;
use Netgen\IbexaSiteApi\API\Values\Content;
use Netgen\IbexaSiteApi\API\Values\Location;

class GlobalVariable
{
    protected Provider $namedObjectProvider;

    protected PermissionService $permissionService;

    protected LoadService $loadService;

    public function __construct(
        Provider $namedObjectProvider,
        PermissionService $permissionService,
        LoadService $loadService
    ) {
        $this->namedObjectProvider = $namedObjectProvider;
        $this->permissionService = $permissionService;
        $this->loadService = $loadService;
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
