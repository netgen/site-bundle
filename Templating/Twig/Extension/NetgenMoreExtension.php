<?php

namespace Netgen\Bundle\MoreBundle\Templating\Twig\Extension;

use Netgen\Bundle\MoreBundle\Helper\PathHelper;
use Netgen\Bundle\MoreBundle\Templating\GlobalHelper;
use eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface;
use Symfony\Component\Intl\Intl;
use Twig_Extension;
use Twig_SimpleFunction;

class NetgenMoreExtension extends Twig_Extension
{
    /**
     * @var \Netgen\Bundle\MoreBundle\Helper\PathHelper
     */
    protected $pathHelper;

    /**
     * @var \Netgen\Bundle\MoreBundle\Templating\GlobalHelper
     */
    protected $globalHelper;

    /**
     * @var \eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface
     */
    protected $localeConverter;

    /**
     * Constructor
     *
     * @param \Netgen\Bundle\MoreBundle\Helper\PathHelper $pathHelper
     * @param \Netgen\Bundle\MoreBundle\Templating\GlobalHelper $globalHelper
     * @param \eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface $localeConverter
     */
    public function __construct( PathHelper $pathHelper, GlobalHelper $globalHelper, LocaleConverterInterface $localeConverter )
    {
        $this->pathHelper = $pathHelper;
        $this->globalHelper = $globalHelper;
        $this->localeConverter = $localeConverter;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'ngmore';
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
                'ngmore_get_path',
                array( $this, 'getPath' ),
                array( 'is_safe' => array( 'html' ) )
            ),
            new Twig_SimpleFunction(
                'ngmore_language_name',
                array( $this, 'getLanguageName' )
            )
        );
    }

    /**
     * Returns the path for specified location ID
     *
     * @param mixed $locationId
     *
     * @return array
     */
    public function getPath( $locationId )
    {
        return $this->pathHelper->getPath( $locationId );
    }

    /**
     * Returns the language name for specified language code
     *
     * @param string $languageCode
     *
     * @return array
     */
    public function getLanguageName( $languageCode )
    {
        if ( !is_string( $languageCode ) || strlen( $languageCode ) < 2 )
        {
            return null;
        }

        $posixLanguageCode = $this->localeConverter->convertToPOSIX( $languageCode );
        if ( $posixLanguageCode === null )
        {
            return null;
        }

        $posixLanguageCode = substr( $posixLanguageCode, 0, 2 );
        $languageName = Intl::getLanguageBundle()->getLanguageName( $posixLanguageCode, null, $posixLanguageCode );
        return ucwords( $languageName );
    }

    /**
     * Returns a list of global variables to add to the existing list.
     *
     * @return array An array of global variables
     */
    public function getGlobals()
    {
        return array( 'ngmore' => $this->globalHelper );
    }
}
