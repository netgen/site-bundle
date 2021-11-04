<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Templating\Twig\Extension;

use eZ\Publish\SPI\Variation\VariationHandler;
use Netgen\Bundle\RemoteMediaBundle\RemoteMedia\RemoteMediaProvider;
use eZ\Publish\Core\FieldType\Image\Value as ImageValue;
use Netgen\Bundle\RemoteMediaBundle\Core\FieldType\RemoteMedia\Value as RemoteImageValue;
use Netgen\EzPlatformSiteApi\API\Values\Content;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface;
use Netgen\Bundle\SiteBundle\Helper\PathHelper;
use Netgen\EzPlatformSiteApi\API\Exceptions\TranslationNotMatchedException;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Symfony\Component\Intl\Intl;
use function mb_substr;
use function ucwords;

class SiteRuntime
{
    protected PathHelper $pathHelper;

    protected LocaleConverterInterface $localeConverter;

    protected LoadService $loadService;

    protected VariationHandler $imageVariationService;

    protected RemoteMediaProvider $remoteMediaProvider;

    public function __construct(
        PathHelper $pathHelper,
        LocaleConverterInterface $localeConverter,
        LoadService $loadService,
        VariationHandler $imageVariationService,
        RemoteMediaProvider $remoteMediaProvider
    ) {
        $this->pathHelper = $pathHelper;
        $this->localeConverter = $localeConverter;
        $this->loadService = $loadService;
        $this->imageVariationService = $imageVariationService;
        $this->remoteMediaProvider = $remoteMediaProvider;
    }

    /**
     * Returns the path for specified location ID.
     *
     * @param int|string $locationId
     */
    public function getLocationPath($locationId, array $options = []): array
    {
        return $this->pathHelper->getPath($locationId, $options);
    }

    /**
     * Returns the language name for specified language code.
     */
    public function getLanguageName(string $languageCode): string
    {
        $posixLanguageCode = $this->localeConverter->convertToPOSIX($languageCode);
        if ($posixLanguageCode === null) {
            return '';
        }

        $posixLanguageCode = mb_substr($posixLanguageCode, 0, 2);
        $languageName = Intl::getLanguageBundle()->getLanguageName($posixLanguageCode, null, $posixLanguageCode);

        return ucwords($languageName);
    }

    /**
     * Returns the name of the content with provided ID.
     *
     * @param int|string $contentId
     */
    public function getContentName($contentId): ?string
    {
        try {
            $content = $this->loadService->loadContent($contentId);
        } catch (UnauthorizedException | NotFoundException | TranslationNotMatchedException $e) {
            return null;
        }

        return $content->name;
    }

    /**
     * Returns the name of the content with located at location with provided ID.
     *
     * @param int|string $locationId
     */
    public function getLocationName($locationId): ?string
    {
        try {
            $location = $this->loadService->loadLocation($locationId);
        } catch (UnauthorizedException | NotFoundException | TranslationNotMatchedException $e) {
            return null;
        }

        return $location->content->name;
    }

    public function getImageUrl(Content $content, string $fieldIdentifier, string $alias): ?string
    {
        $field = $content->getField($fieldIdentifier);

        if ($field->value instanceof RemoteImageValue) {
            return $this->remoteMediaProvider->buildVariation($field->value, $content->contentInfo->contentTypeIdentifier, $alias)->url;
        }

        if ($field->value instanceof ImageValue) {
            return $this->imageVariationService->getVariation($field->innerField, $content->innerVersionInfo, $alias)->uri;
        }

        return '/';
    }
}
