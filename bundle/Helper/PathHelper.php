<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Helper;

use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use function array_map;
use function array_shift;
use function in_array;
use function is_array;

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
     * @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface
     */
    protected $urlGenerator;

    public function __construct(
        LoadService $loadService,
        ConfigResolverInterface $configResolver,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->loadService = $loadService;
        $this->configResolver = $configResolver;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Returns the path array for provided location ID.
     */
    public function getPath(int $locationId, array $options = []): array
    {
        $optionsResolver = new OptionsResolver();
        $this->configureOptions($optionsResolver);
        $options = $optionsResolver->resolve($options);

        $excludedContentTypes = [];
        if (
            !$options['use_all_content_types']
            && $this->configResolver->hasParameter('path_helper.excluded_content_types', 'ngsite')
        ) {
            $excludedContentTypes = $this->configResolver->getParameter('path_helper.excluded_content_types', 'ngsite');
            if (!is_array($excludedContentTypes)) {
                $excludedContentTypes = [];
            }
        }

        // The root location can be defined at site access level
        $rootLocationId = (int) $this->configResolver->getParameter('content.tree_root.location_id');

        $path = array_map('intval', $this->loadService->loadLocation($locationId)->path);

        // Shift of location "1" from path as it is not a fully valid location and not readable by most users
        array_shift($path);

        $pathArray = [];
        $rootLocationFound = false;
        foreach ($path as $index => $pathItem) {
            if ($pathItem === $rootLocationId) {
                $rootLocationFound = true;
            }

            if (!$rootLocationFound) {
                continue;
            }

            try {
                $location = $this->loadService->loadLocation($pathItem);
            } catch (UnauthorizedException $e) {
                return [];
            }

            if (!in_array($location->contentInfo->contentTypeIdentifier, $excludedContentTypes, true)) {
                $pathArray[] = [
                    'text' => $location->contentInfo->name,
                    'url' => $location->id !== (int) $locationId ?
                        $this->urlGenerator->generate(
                            '',
                            [RouteObjectInterface::ROUTE_OBJECT => $location],
                            $options['absolute_url'] ?
                                UrlGeneratorInterface::ABSOLUTE_URL :
                                UrlGeneratorInterface::ABSOLUTE_PATH
                        ) :
                        false,
                    'location' => $location,
                ];
            }
        }

        return $pathArray;
    }

    protected function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setRequired('use_all_content_types');
        $optionsResolver->setAllowedTypes('use_all_content_types', 'bool');
        $optionsResolver->setDefault('use_all_content_types', false);

        $optionsResolver->setRequired('absolute_url');
        $optionsResolver->setAllowedTypes('absolute_url', 'bool');
        $optionsResolver->setDefault('absolute_url', false);
    }
}
