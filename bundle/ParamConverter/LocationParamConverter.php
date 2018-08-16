<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\ParamConverter;

use eZ\Bundle\EzPublishCoreBundle\Converter\RepositoryParamConverter;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Netgen\EzPlatformSiteApi\API\Values\Location;

class LocationParamConverter extends RepositoryParamConverter
{
    /**
     * @var \Netgen\EzPlatformSiteApi\API\LoadService
     */
    protected $loadService;

    public function __construct(LoadService $loadService)
    {
        $this->loadService = $loadService;
    }

    protected function getSupportedClass(): string
    {
        return Location::class;
    }

    protected function getPropertyName(): string
    {
        return 'locationId';
    }

    protected function loadValueObject($id)
    {
        return $this->loadService->loadLocation($id);
    }
}
