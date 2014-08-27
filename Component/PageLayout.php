<?php

namespace Netgen\Bundle\MoreBundle\Component;

class PageLayout
{
    protected $kernel;
    protected $repository;
    protected $configResolver;

    private $mainCategoryLocationId;

    /**
     * Constructor
     *
     * @param  $kernel
     */
    public function __construct( $kernel, $repository, $configResolver )
    {
        $this->kernel = $kernel;
        $this->repository = $repository;
        $this->configResolver = $configResolver;
    }

    public function getMainCategoryLocationId($locationId) {
        if ($locationId == 0)
            return 0;

        if (! isset($this->mainCategoryLocationId)) {
            $locationService = $this->repository->getLocationService();
            $location = $locationService->loadLocation( $locationId );
            $mainCategoryId = 2;
            $pathArray = explode('/',$location->pathString);
            if (is_numeric($pathArray[3])) {
                $mainCategoryId = $pathArray[3];
            }
            $this->mainCategoryLocationId = $mainCategoryId;
        }
        return $this->mainCategoryLocationId;
    }

    public function getParams($locationId = 0, $uri = '' )
    {
        return array(
                'mainCategoryLocationId' => $this->getMainCategoryLocationId($locationId)
        );
    }
}
