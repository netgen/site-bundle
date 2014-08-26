<?php

namespace Netgen\Bundle\MoreBundle\Component;

use NgMoreFunctionCollection;

class PageLayout
{
    protected $kernel;
    protected $repository;
    protected $configResolver;

    private $layoutContent;
    private $mainCategoryLocationId;
    private $rootLocation;
    private $siteInfoLocation;

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

    public function getSiteInfoLocation() {
        if (! isset($this->siteInfoLocation)) {
            $locationService = $this->repository->getLocationService();
            $siteInfoLocationId = $this->configResolver->getParameter( 'SpecialNodes.SiteInfoNode', 'ngmore' );
            $this->siteInfoLocation = $locationService->loadLocation($siteInfoLocationId);
        }
        return $this->siteInfoLocation;
    }

    public function getRootLocation() {
        if (! isset($this->rootLocation)) {
            $locationService = $this->repository->getLocationService();
            $this->rootLocation = $locationService->loadLocation(2);
        }
        return $this->rootLocation;
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

    public function getLayoutLocation( $locationId, $uri ) {
        if (! isset($this->layoutContent)) {

            $legacyKernelClosure = $this->kernel;

            $layout = $legacyKernelClosure()->runCallback(
                function () use ( $locationId, $uri )
                {
                    return NgMoreFunctionCollection::fetchLayout($locationId, $uri);
                }
            );

            if ($layout['result'] == false)
                return;

            if (isset($layout['result']->ID))
                $layoutId = $layout['result']->ID;
            elseif (isset($layout['result']->ContentObjectID))
                $layoutId = $layout['result']->ContentObjectID;
            else
                return;

            $contentService = $this->repository->getContentService();
            $this->layoutContent = $contentService->loadContent( $layoutId );
        }
        return $this->layoutContent;
    }

    public function getMetadata($locationId = 0) {
        $metaData = array();

        if ($locationId > 0) {
            $location = $this->repository->getLocationService()->loadLocation( $locationId );
            $content = $this->repository->getContentService()->loadContent( $location->contentId );
            $metaData['title'] = $content->getFieldValue('metadata')->title;
        }
        return $metaData;
    }

    public function getParams($locationId = 0, $uri = '' )
    {
        return array(
                'layout' =>  $this->getLayoutLocation( $locationId, $uri),
                'locationId' => $locationId,
                'mainCategoryLocationId' => $this->getMainCategoryLocationId($locationId),
                'rootLocation' => $this->getRootLocation(),
                'siteInfoLocation' => $this->getSiteInfoLocation(),
                'metaData' => $this->getMetadata( $locationId )
        );
    }
}
