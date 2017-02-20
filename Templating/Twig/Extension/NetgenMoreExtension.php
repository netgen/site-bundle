<?php

namespace Netgen\Bundle\MoreBundle\Templating\Twig\Extension;

use Netgen\EzPlatformSiteApi\API\Values\ContentInfo;
use eZ\Publish\Core\Helper\TranslationHelper;
use Netgen\Bundle\MoreBundle\Helper\PathHelper;
use Netgen\Bundle\MoreBundle\Templating\GlobalVariable;
use eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface;
use Netgen\EzPlatformSiteApi\API\Values\Content;
use eZ\Publish\Core\MVC\Symfony\Security\Authorization\Attribute;
use eZ\Publish\API\Repository\Values\ValueObject;
use eZ\Publish\API\Repository\Repository;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Intl\Intl;
use Twig_Extension_GlobalsInterface;
use Twig_Extension;
use Twig_SimpleFunction;

class NetgenMoreExtension extends Twig_Extension implements Twig_Extension_GlobalsInterface
{
    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * @var \Netgen\EzPlatformSiteApi\API\LoadService
     */
    protected $loadService;

    /**
     * @var \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

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
     * @var \eZ\Publish\Core\Helper\TranslationHelper
     */
    protected $translationHelper;

    /**
     * Constructor.
     *
     * @param \eZ\Publish\API\Repository\Repository $repository
     * @param \Netgen\EzPlatformSiteApi\API\LoadService $loadService
     * @param \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface $authorizationChecker
     * @param \Netgen\Bundle\MoreBundle\Helper\PathHelper $pathHelper
     * @param \Netgen\Bundle\MoreBundle\Templating\GlobalVariable $globalVariable
     * @param \eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface $localeConverter
     * @param \eZ\Publish\Core\Helper\TranslationHelper $translationHelper
     */
    public function __construct(
        Repository $repository,
        LoadService $loadService,
        AuthorizationCheckerInterface $authorizationChecker,
        PathHelper $pathHelper,
        GlobalVariable $globalVariable,
        LocaleConverterInterface $localeConverter,
        TranslationHelper $translationHelper
    ) {
        $this->repository = $repository;
        $this->loadService = $loadService;
        $this->authorizationChecker = $authorizationChecker;
        $this->pathHelper = $pathHelper;
        $this->globalVariable = $globalVariable;
        $this->localeConverter = $localeConverter;
        $this->translationHelper = $translationHelper;
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
                array($this, 'getLocationPath'),
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'ngmore_language_name',
                array($this, 'getLanguageName')
            ),
            new Twig_SimpleFunction(
                'ngmore_content_name',
                array($this, 'getContentName')
            ),
            new Twig_SimpleFunction(
                'ngmore_owner',
                array($this, 'getOwner')
            ),
            new Twig_SimpleFunction(
                'ngmore_is_granted',
                array($this, 'isGranted')
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
     * Returns content name for specified content ID.
     *
     * @param \Netgen\EzPlatformSiteApi\API\Values\ContentInfo|\Netgen\EzPlatformSiteApi\API\Values\Content|int $content
     * @param string $forcedLanguage
     *
     * @return string
     */
    public function getContentName($content, $forcedLanguage = null)
    {
        if (!$content instanceof Content && !$content instanceof ContentInfo) {
            $contentInfo = $this->repository->getContentService()->loadContentInfo($content);
        } elseif ($content instanceof Content) {
            $contentInfo = $content->innerContent->contentInfo;
        }

        return $this->translationHelper->getTranslatedContentNameByContentInfo($contentInfo, $forcedLanguage);
    }

    /**
     * Returns owner content for specified content.
     *
     * @param \Netgen\EzPlatformSiteApi\API\Values\Content
     *
     * @return \Netgen\EzPlatformSiteApi\API\Values\Content
     */
    public function getOwner(Content $content)
    {
        $ownerId = $content->contentInfo->ownerId;

        return $this->repository->sudo(
            function (Repository $repository) use ($ownerId) {
                return $this->loadService->loadContent($ownerId);
            }
        );
    }

    /**
     * Indicates if the current user is allowed to perform an action given by the function on the given
     * objects.
     *
     * @param string $module The module, aka controller identifier to check permissions on
     * @param string $function The function, aka the controller action to check permissions on
     * @param \eZ\Publish\API\Repository\Values\ValueObject $object The object to check if the user has access to
     * @param mixed $targets The location, parent or "assignment" value object, or an array of the same
     *
     * @return bool
     */
    public function isGranted($module, $function, ValueObject $object = null, $targets = null)
    {
        $attribute = new Attribute($module, $function);
        if ($object instanceof ValueObject) {
            $attribute->limitations['valueObject'] = $object;
            if ($targets !== null) {
                $attribute->limitations['targets'] = $targets;
            }
        }

        return $this->authorizationChecker->isGranted($attribute);
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
