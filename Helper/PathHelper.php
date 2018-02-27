<?php

namespace Netgen\Bundle\MoreBundle\Helper;

use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class PathHelper
{
    /**
     * @var \Netgen\EzPlatformSiteApi\API\LoadService
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
     * @param \Netgen\EzPlatformSiteApi\API\LoadService $loadService
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
     * @param array $options
     *
     * @return array
     */
    public function getPath($locationId, array $options = array())
    {
        $optionsResolver = new OptionsResolver();
        $this->configureOptions($optionsResolver);
        $options = $optionsResolver->resolve($options);

        $excludedContentTypes = array();
        if (
            $this->configResolver->hasParameter('path_helper.excluded_content_types', 'ngmore') &&
            !$options['use_all_content_types']
        ) {
            $excludedContentTypes = $this->configResolver->getParameter('path_helper.excluded_content_types', 'ngmore');
            if (!is_array($excludedContentTypes)) {
                $excludedContentTypes = array();
            }
        }

        // The root location can be defined at site access level
        $rootLocationId = (int) $this->configResolver->getParameter('content.tree_root.location_id');

        $path = $this->loadService->loadLocation($locationId)->path;

        // Shift of location "1" from path as it is not a fully valid location and not readable by most users
        array_shift($path);

        $pathArray = array();
        $rootLocationFound = false;
        foreach ($path as $index => $pathItem) {
            if ((int) $pathItem === $rootLocationId) {
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

            if (!in_array($location->contentInfo->contentTypeIdentifier, $excludedContentTypes, true)) {
                $pathArray[] = array(
                    'text' => $location->contentInfo->name,
                    'url' => $location->id !== (int) $locationId ?
                        $this->router->generate(
                            $location,
                            array(),
                            $options['absolute_url'] ?
                                UrlGeneratorInterface::ABSOLUTE_URL :
                                UrlGeneratorInterface::ABSOLUTE_PATH
                        ) :
                        false,
                    'location' => $location,
                );
            }
        }

        return $pathArray;
    }

    protected function configureOptions(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setRequired('use_all_content_types');
        $optionsResolver->setAllowedTypes('use_all_content_types', 'bool');
        $optionsResolver->setDefault('use_all_content_types', false);

        $optionsResolver->setRequired('absolute_url');
        $optionsResolver->setAllowedTypes('absolute_url', 'bool');
        $optionsResolver->setDefault('absolute_url', false);
    }
}
