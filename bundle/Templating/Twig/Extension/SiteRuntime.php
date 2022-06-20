<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Templating\Twig\Extension;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface;
use Netgen\Bundle\SiteBundle\Helper\PathHelper;
use Netgen\EzPlatformSiteApi\API\Exceptions\TranslationNotMatchedException;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Symfony\Component\Intl\Languages;
use function ceil;
use function mb_substr;
use function str_word_count;
use function ucwords;

class SiteRuntime
{
    private const WORDS_PER_MINUTE = 230;

    protected PathHelper $pathHelper;

    protected LocaleConverterInterface $localeConverter;

    protected LoadService $loadService;

    public function __construct(PathHelper $pathHelper, LocaleConverterInterface $localeConverter, LoadService $loadService)
    {
        $this->pathHelper = $pathHelper;
        $this->localeConverter = $localeConverter;
        $this->loadService = $loadService;
    }

    /**
     * Returns the path for specified location ID.
     */
    public function getLocationPath(int $locationId, array $options = []): array
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
        $languageName = Languages::getName($posixLanguageCode, $posixLanguageCode);

        return ucwords($languageName);
    }

    /**
     * Returns the name of the content with provided ID.
     */
    public function getContentName(int $contentId): ?string
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
     */
    public function getLocationName(int $locationId): ?string
    {
        try {
            $location = $this->loadService->loadLocation($locationId);
        } catch (UnauthorizedException|NotFoundException|TranslationNotMatchedException $e) {
            return null;
        }

        return $location->content->name;
    }

    public function calculateReadingTime(string $text): int
    {
        $wordCount = str_word_count($text);
        $readingTime = ceil($wordCount / self::WORDS_PER_MINUTE);

        return $readingTime === false || $readingTime < 1 ? 1 : (int) $readingTime;
    }
}
