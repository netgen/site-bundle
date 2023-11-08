<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Helper;

use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Netgen\IbexaSiteApi\API\LoadService;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function array_map;
use function array_shift;
use function in_array;
use function is_array;

final class PathHelper
{
    public function __construct(
        private LoadService $loadService,
        private ConfigResolverInterface $configResolver,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * Returns the path array for provided location ID.
     *
     * @param array<string, mixed> $options
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPath(int $locationId, array $options = []): array
    {
        $optionsResolver = new OptionsResolver();
        $this->configureOptions($optionsResolver);
        $options = $optionsResolver->resolve($options);

        $useAllContentTypes = $options['use_all_content_types'];
        $showCurrentLocation = $options['show_current_location'];

        $excludedContentTypes = [];
        if ($this->configResolver->hasParameter('path_helper.excluded_content_types', 'ngsite')) {
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
        foreach ($path as $pathItem) {
            if ($pathItem === $rootLocationId) {
                $rootLocationFound = true;
            }

            if (!$rootLocationFound) {
                continue;
            }

            try {
                $location = $this->loadService->loadLocation($pathItem);
            } catch (UnauthorizedException) {
                return [];
            }

            if (!$showCurrentLocation && $location->id === $locationId) {
                continue;
            }

            if (!$useAllContentTypes && in_array($location->contentInfo->contentTypeIdentifier, $excludedContentTypes, true)) {
                continue;
            }

            $disableItemUrl = $useAllContentTypes && in_array($location->contentInfo->contentTypeIdentifier, $excludedContentTypes, true);

            $itemName = $location->contentInfo->name;
            if ($location->content->hasField('breadcrumb_title') && !$location->content->getField('breadcrumb_title')->isEmpty()) {
                $itemName = $location->content->getField('breadcrumb_title')->value->text;
            }

            $pathArray[] = [
                'text' => $itemName,
                'url' => !$disableItemUrl && $location->id !== $locationId ?
                    $this->urlGenerator->generate(
                        '',
                        [RouteObjectInterface::ROUTE_OBJECT => $location],
                        $options['absolute_url'] ?
                            UrlGeneratorInterface::ABSOLUTE_URL :
                            UrlGeneratorInterface::ABSOLUTE_PATH,
                    ) :
                    false,
                'location' => $location,
            ];
        }

        return $pathArray;
    }

    private function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setRequired('use_all_content_types');
        $optionsResolver->setAllowedTypes('use_all_content_types', 'bool');
        $optionsResolver->setDefault('use_all_content_types', false);

        $optionsResolver->setRequired('show_current_location');
        $optionsResolver->setAllowedTypes('show_current_location', 'bool');
        $optionsResolver->setDefault('show_current_location', false);

        $optionsResolver->setRequired('absolute_url');
        $optionsResolver->setAllowedTypes('absolute_url', 'bool');
        $optionsResolver->setDefault('absolute_url', false);
    }
}
