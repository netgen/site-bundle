<?php

namespace Netgen\Bundle\MoreBundle\Templating\Twig\Extension;

use eZ\Publish\Core\Helper\TranslationHelper;
use Netgen\Bundle\MoreBundle\Helper\PathHelper;
use Netgen\Bundle\MoreBundle\Templating\GlobalHelper;
use eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Repository;
use Symfony\Component\Intl\Intl;
use Twig_Extension;
use Twig_SimpleFunction;

class NetgenMoreExtension extends Twig_Extension
{

    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * @var \eZ\Publish\Core\Helper\TranslationHelper
     */
    protected $translationHelper;

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
     * @param \eZ\Publish\API\Repository\Repository $repository
     * @param \eZ\Publish\Core\Helper\TranslationHelper $translationHelper
     * @param \Netgen\Bundle\MoreBundle\Helper\PathHelper $pathHelper
     * @param \Netgen\Bundle\MoreBundle\Templating\GlobalHelper $globalHelper
     * @param \eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface $localeConverter
     */
    public function __construct(
        Repository $repository,
        TranslationHelper $translationHelper,
        PathHelper $pathHelper,
        GlobalHelper $globalHelper,
        LocaleConverterInterface $localeConverter
    )
    {
        $this->repository = $repository;
        $this->translationHelper = $translationHelper;
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
                'ngmore_location_path',
                array( $this, 'getLocationPath' ),
                array( 'is_safe' => array( 'html' ) )
            ),
            new Twig_SimpleFunction(
                'ngmore_language_name',
                array( $this, 'getLanguageName' )
            ),
            new Twig_SimpleFunction(
                'ngmore_content_type_identifier',
                array( $this, 'getContentTypeIdentifier' )
            ),
            new Twig_SimpleFunction(
                'ngmore_owner_name',
                array( $this, 'getOwnerName' )
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
    public function getLocationPath( $locationId )
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
     * Returns content type identifier for specified content type ID
     *
     * @param mixed $contentTypeId
     * @return string
     */
    public function getContentTypeIdentifier( $contentTypeId )
    {
        return $this->repository->getContentTypeService()->loadContentType( $contentTypeId )->identifier;
    }

    /**
     * @param \eZ\Publish\API\Repository\Values\Content\Content $content Must be a valid Content or ContentInfo object.
     * @param string $forcedLanguage Locale we want the content name translation in (e.g. "fre-FR"). Null by default (takes current locale)
     *
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType When $content is not a valid Content or ContentInfo object.
     *
     * @return string
     */
    public function getOwnerName( Content $content, $forcedLanguage = null )
    {
        $ownerContentId = $content->contentInfo->ownerId;
        $ownerContent = $this->repository->sudo(
            function ( $repository ) use ( $ownerContentId )
            {
                /** @var \eZ\Publish\API\Repository\Repository $repository */
                return $repository->getContentService()->loadContent( $ownerContentId );
            }
        );

        return $this->translationHelper->getTranslatedContentName( $ownerContent, $forcedLanguage );
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
