<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Templating\Twig\Extension;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use eZ\Publish\Core\FieldType\Image\Value as ImageValue;
use eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface;
use eZ\Publish\SPI\Variation\VariationHandler;
use Netgen\Bundle\RemoteMediaBundle\Core\FieldType\RemoteMedia\Value as RemoteImageValue;
use Netgen\Bundle\RemoteMediaBundle\RemoteMedia\RemoteMediaProvider;
use Netgen\Bundle\SiteBundle\Helper\PathHelper;
use Netgen\EzPlatformSiteApi\API\Exceptions\TranslationNotMatchedException;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Netgen\EzPlatformSiteApi\API\Values\Content;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Intl\Intl;
use Throwable;

use function ceil;
use function mb_substr;
use function str_word_count;
use function ucwords;

class SiteRuntime
{
    private const WORDS_PER_MINUTE = 230;

    protected bool $debug;

    protected PathHelper $pathHelper;

    protected LocaleConverterInterface $localeConverter;

    protected LoadService $loadService;

    protected VariationHandler $imageVariationService;

    protected ?RemoteMediaProvider $remoteMediaProvider;

    protected LoggerInterface $logger;

    public function __construct(
        bool $debug,
        PathHelper $pathHelper,
        LocaleConverterInterface $localeConverter,
        LoadService $loadService,
        VariationHandler $imageVariationService,
        ?RemoteMediaProvider $remoteMediaProvider = null,
        ?LoggerInterface $logger = null
    ) {
        $this->debug = $debug;
        $this->pathHelper = $pathHelper;
        $this->localeConverter = $localeConverter;
        $this->loadService = $loadService;
        $this->imageVariationService = $imageVariationService;
        $this->remoteMediaProvider = $remoteMediaProvider;
        $this->logger = $logger ?: new NullLogger();
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
        } catch (UnauthorizedException|NotFoundException|TranslationNotMatchedException $e) {
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
        } catch (UnauthorizedException|NotFoundException|TranslationNotMatchedException $e) {
            return null;
        }

        return $location->content->name;
    }

    public function getImageUrl(Content $content, string $fieldIdentifier, string $alias): ?string
    {
        $field = $content->getField($fieldIdentifier);

        try {
            if ($this->remoteMediaProvider !== null && $field->value instanceof RemoteImageValue) {
                return $this->remoteMediaProvider->buildVariation(
                    $field->value,
                    $content->contentInfo->contentTypeIdentifier,
                    $alias,
                )->url;
            }

            if ($field->value instanceof ImageValue) {
                return $this->imageVariationService->getVariation(
                    $field->innerField,
                    $content->innerVersionInfo,
                    $alias,
                )->uri;
            }
        } catch (Throwable $e) {
            if ($this->debug !== true) {
                $this->logger->critical($e->getMessage());

                return '/';
            }

            throw $e;
        }

        return '/';
    }

    public function calculateReadingTime(string $text): int
    {
        $wordCount = str_word_count($text);
        $readingTime = ceil($wordCount / self::WORDS_PER_MINUTE);

        return $readingTime === false || $readingTime < 1 ? 1 : (int) $readingTime;
    }
}
