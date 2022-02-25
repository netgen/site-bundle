<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Templating;

use Ibexa\Contracts\Core\Repository\Repository;
use Netgen\Bundle\IbexaSiteApiBundle\NamedObject\Provider;
use Netgen\IbexaSiteApi\API\LoadService;
use Netgen\IbexaSiteApi\API\Values\Content;
use Netgen\IbexaSiteApi\API\Values\Location;

class GlobalVariable
{
    protected Provider $namedObjectProvider;

    protected Repository $repository;

    protected LoadService $loadService;

    public function __construct(
        Provider $namedObjectProvider,
        Repository $repository,
        LoadService $loadService
    ) {
        $this->namedObjectProvider = $namedObjectProvider;
        $this->repository = $repository;
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
        $currentUser = $this->repository->getPermissionResolver()->getCurrentUserReference();

        return $this->loadService->loadContent($currentUser->getUserId());
    }
}
