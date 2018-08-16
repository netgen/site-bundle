<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Templating\Twig\Extension;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface;
use Netgen\Bundle\MoreBundle\Helper\PathHelper;
use Netgen\EzPlatformSiteApi\API\Exceptions\TranslationNotMatchedException;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Symfony\Component\Intl\Intl;

class NetgenMoreRuntime
{
    /**
     * @var \Netgen\Bundle\MoreBundle\Helper\PathHelper
     */
    protected $pathHelper;

    /**
     * @var \eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface
     */
    protected $localeConverter;

    /**
     * @var \Netgen\EzPlatformSiteApi\API\LoadService
     */
    protected $loadService;

    public function __construct(PathHelper $pathHelper, LocaleConverterInterface $localeConverter, LoadService $loadService)
    {
        $this->pathHelper = $pathHelper;
        $this->localeConverter = $localeConverter;
        $this->loadService = $loadService;
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

        $posixLanguageCode = substr($posixLanguageCode, 0, 2);
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
}
