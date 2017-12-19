<?php

namespace Netgen\Bundle\MoreBundle\Templating\Twig\Extension;

use eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface;
use Netgen\Bundle\MoreBundle\Helper\PathHelper;
use Netgen\Bundle\MoreBundle\Templating\GlobalVariable;
use Symfony\Component\Intl\Intl;
use Twig_Extension;
use Twig_Extension_GlobalsInterface;
use Twig_SimpleFunction;

class NetgenMoreExtension extends Twig_Extension implements Twig_Extension_GlobalsInterface
{
    /**
     * @var \Netgen\Bundle\MoreBundle\Helper\PathHelper
     */
    protected $pathHelper;

    /**
     * @var \Netgen\Bundle\MoreBundle\Templating\GlobalVariable
     */
    protected $globalVariable;

    /**
     * @var \eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface
     */
    protected $localeConverter;

    /**
     * Constructor.
     *
     * @param \Netgen\Bundle\MoreBundle\Helper\PathHelper $pathHelper
     * @param \Netgen\Bundle\MoreBundle\Templating\GlobalVariable $globalVariable
     * @param \eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface $localeConverter
     */
    public function __construct(
        PathHelper $pathHelper,
        GlobalVariable $globalVariable,
        LocaleConverterInterface $localeConverter
    ) {
        $this->pathHelper = $pathHelper;
        $this->globalVariable = $globalVariable;
        $this->localeConverter = $localeConverter;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction(
                'ngmore_location_path',
                array($this, 'getLocationPath'),
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'ngmore_language_name',
                array($this, 'getLanguageName')
            ),
        );
    }

    /**
     * Returns the path for specified location ID.
     *
     * @param mixed $locationId
     * @param bool $includeAllContentTypes
     *
     * @return array
     */
    public function getLocationPath($locationId, $includeAllContentTypes = false)
    {
        return $this->pathHelper->getPath($locationId, !$includeAllContentTypes);
    }

    /**
     * Returns the language name for specified language code.
     *
     * @param string $languageCode
     *
     * @return array
     */
    public function getLanguageName($languageCode)
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

    /**
     * Returns a list of global variables to add to the existing list.
     *
     * @return array An array of global variables
     */
    public function getGlobals()
    {
        return array('ngmore' => $this->globalVariable);
    }
}
