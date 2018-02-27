<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Templating\Twig\Extension;

use eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface;
use Netgen\Bundle\MoreBundle\Helper\PathHelper;
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

    public function __construct(PathHelper $pathHelper, LocaleConverterInterface $localeConverter)
    {
        $this->pathHelper = $pathHelper;
        $this->localeConverter = $localeConverter;
    }

    /**
     * Returns the path for specified location ID.
     *
     * @param mixed $locationId
     */
    public function getLocationPath($locationId, array $options = array()): array
    {
        return $this->pathHelper->getPath($locationId, $options);
    }

    /**
     * Returns the language name for specified language code.
     */
    public function getLanguageName(string $languageCode): ?string
    {
        if (!is_string($languageCode) || strlen($languageCode) < 2) {
            return null;
        }

        $posixLanguageCode = $this->localeConverter->convertToPOSIX($languageCode);
        if ($posixLanguageCode === null) {
            return null;
        }

        $posixLanguageCode = substr($posixLanguageCode, 0, 2);
        $languageName = Intl::getLanguageBundle()->getLanguageName($posixLanguageCode, null, $posixLanguageCode);

        return ucwords($languageName);
    }
}
