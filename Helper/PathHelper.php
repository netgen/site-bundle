<?php

namespace Netgen\Bundle\MoreBundle\Helper;

use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\Helper\TranslationHelper;
use Symfony\Component\Routing\RouterInterface;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;

class PathHelper
{
    /**
     * @var \eZ\Publish\API\Repository\LocationService
     */
    protected $locationService;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var \eZ\Publish\Core\Helper\TranslationHelper
     */
    protected $translationHelper;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    protected $router;

    /**
     * @var  \eZ\Publish\API\Repository\ContentTypeService
     */
    protected $contentTypeService;

    /**
     * Constructor.
     *
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     * @param \eZ\Publish\Core\Helper\TranslationHelper $translationHelper
     * @param \Symfony\Component\Routing\RouterInterface $router
     * @param \eZ\Publish\API\Repository\ContentTypeService $contentTypeService
     */
    public function __construct(
        LocationService $locationService,
        ConfigResolverInterface $configResolver,
        TranslationHelper $translationHelper,
        RouterInterface $router,
        ContentTypeService $contentTypeService
    ) {
        $this->locationService = $locationService;
        $this->configResolver = $configResolver;
        $this->translationHelper = $translationHelper;
        $this->router = $router;
        $this->contentTypeService = $contentTypeService;
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

        $pathArray = array();
        $path = $this->locationService->loadLocation($locationId)->path;

        // The root location can be defined at site access level
        $rootLocationId = $this->configResolver->getParameter('content.tree_root.location_id');

        $isRootLocation = false;

        // Shift of location "1" from path as it is not a fully valid location and not readable by most users
        array_shift($path);

        $location = null;
        for ($i = 0; $i < count($path); ++$i) {
            // if root location hasn't been found yet
            if (!$isRootLocation) {
                // If we reach the root location, we begin to add item to the path array from it
                if ($path[$i] == $rootLocationId) {
                    try {
                        $location = $this->locationService->loadLocation($path[$i]);
                    } catch (UnauthorizedException $e) {
                        return array();
                    }

                    $isRootLocation = true;
                    $contentType = $this->contentTypeService->loadContentType($location->contentInfo->contentTypeId);
                    if (!in_array($contentType->identifier, $excludedContentTypes)) {
                        $pathArray[] = array(
                            'text' => $this->translationHelper->getTranslatedContentNameByContentInfo($location->contentInfo),
                            'url' => $location->id != $locationId ? $this->router->generate($location) : false,
                            'locationId' => $location->id,
                            'contentId' => $location->contentId,
                            'contentTypeId' => $location->contentInfo->contentTypeId,
                            'contentTypeIdentifier' => $contentType->identifier,
                        );
                    }
                }
            }
            // The root location has already been reached, so we can add items to the path array
            else {
                try {
                    $location = $this->locationService->loadLocation($path[$i]);
                } catch (UnauthorizedException $e) {
                    return array();
                }

                $contentType = $this->contentTypeService->loadContentType($location->contentInfo->contentTypeId);
                if (!in_array($contentType->identifier, $excludedContentTypes)) {
                    $pathArray[] = array(
                        'text' => $this->translationHelper->getTranslatedContentNameByContentInfo($location->contentInfo),
                        'url' => $location->id != $locationId ? $this->router->generate($location) : false,
                        'locationId' => $location->id,
                        'contentId' => $location->contentId,
                        'contentTypeId' => $location->contentInfo->contentTypeId,
                        'contentTypeIdentifier' => $contentType->identifier,
                    );
                }
            }
        }

        return $pathArray;
    }
}
