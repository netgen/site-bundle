<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Templating;

use eZ\Publish\API\Repository\Repository;
use Netgen\Bundle\MoreBundle\Helper\SiteInfoHelper;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Netgen\EzPlatformSiteApi\API\Values\Content;
use Netgen\EzPlatformSiteApi\API\Values\Location;

class GlobalVariable
{
    /**
     * @var \Netgen\Bundle\MoreBundle\Helper\SiteInfoHelper
     */
    protected $siteInfoHelper;

    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * @var \Netgen\EzPlatformSiteApi\API\LoadService
     */
    protected $loadService;

    public function __construct(
        SiteInfoHelper $siteInfoHelper,
        Repository $repository,
        LoadService $loadService
    ) {
        $this->siteInfoHelper = $siteInfoHelper;
        $this->repository = $repository;
        $this->loadService = $loadService;
    }

    public function getSiteInfoLocation(): Location
    {
        return $this->siteInfoHelper->getSiteInfoLocation();
    }

    public function getSiteInfoContent(): Content
    {
        return $this->siteInfoHelper->getSiteInfoContent();
    }

    public function getCurrentUserContent(): Content
    {
        $currentUser = $this->repository->getPermissionResolver()->getCurrentUserReference();

        return $this->loadService->loadContent($currentUser->getUserId());
    }
}
