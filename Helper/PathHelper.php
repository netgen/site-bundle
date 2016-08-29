<?php

namespace Netgen\Bundle\MoreBundle\Helper;

use Netgen\EzPlatformSite\API\LoadService;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Component\Routing\RouterInterface;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;

class PathHelper
{
    /**
     * @var \Netgen\EzPlatformSite\API\LoadService
     */
    protected $loadService;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    protected $router;

    /**
     * Constructor.
     *
     * @param \Netgen\EzPlatformSite\API\LoadService $loadService
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     * @param \Symfony\Component\Routing\RouterInterface $router
     */
    public function __construct(
        LoadService $loadService,
        ConfigResolverInterface $configResolver,
        RouterInterface $router
    ) {
        $this->loadService = $loadService;
        $this->configResolver = $configResolver;
        $this->router = $router;
    }

    /**
     * Returns the path array for location ID.
     *
     * @param mixed $locationId
     * @param bool $doExcludeContentTypes
     *
     * @return array
     */
    public function getPath($locationId, $doExcludeContentTypes = false)
    {
        $excludedContentTypes = array();
        if (
            $this->configResolver->hasParameter('path_helper.excluded_content_types', 'ngmore') &&
            $doExcludeContentTypes
        ) {
            $excludedContentTypes = $this->configResolver->getParameter('path_helper.excluded_content_types', 'ngmore');
            if (!is_array($excludedContentTypes)) {
                $excludedContentTypes = array();
            }
        }

        // The root location can be defined at site access level
        $rootLocationId = $this->configResolver->getParameter('content.tree_root.location_id');

        $path = $this->loadService->loadLocation($locationId)->path;

        // Shift of location "1" from path as it is not a fully valid location and not readable by most users
        array_shift($path);

        $pathArray = array();
        $rootLocationFound = false;
        foreach ($path as $index => $pathItem) {
            if ($pathItem == $rootLocationId) {
                $rootLocationFound = true;
            }

            if (!$rootLocationFound) {
                continue;
            }

            try {
                $location = $this->loadService->loadLocation($pathItem);
            } catch (UnauthorizedException $e) {
                return array();
            }

            if (!in_array($location->contentInfo->contentTypeIdentifier, $excludedContentTypes)) {
                $pathArray[] = array(
                    'text' => $location->contentInfo->name,
                    'url' => $location->id != $locationId ?
                        $this->router->generate($location) :
                        false,
                    'location' => $location,
                );
            }
        }

        return $pathArray;
    }
}
