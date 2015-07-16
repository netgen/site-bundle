<?php

namespace Netgen\Bundle\MoreBundle\XmlText\Converter;

use eZ\Publish\Core\FieldType\XmlText\Converter;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\LocationService;
use Symfony\Component\Routing\RouterInterface;
use eZ\Publish\Core\Helper\TranslationHelper;
use eZ\Publish\Core\Helper\FieldHelper;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use Psr\Log\LoggerInterface;
use DOMDocument;

class EzLinkDirectDownload implements Converter
{
    /**
     * @var \eZ\Publish\API\Repository\LocationService
     */
    protected $locationService;

    /**
     * @var \eZ\Publish\API\Repository\ContentService
     */
    protected $contentService;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    protected $router;

    /**
     * @var \eZ\Publish\Core\Helper\TranslationHelper
     */
    protected $translationHelper;

    /**
     * @var \eZ\Publish\Core\Helper\FieldHelper
     */
    protected $fieldHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     * @param \Symfony\Component\Routing\RouterInterface $router
     * @param \eZ\Publish\Core\Helper\TranslationHelper $translationHelper
     * @param \eZ\Publish\Core\Helper\FieldHelper $fieldHelper
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        LocationService $locationService,
        ContentService $contentService,
        RouterInterface $router,
        TranslationHelper $translationHelper,
        FieldHelper $fieldHelper,
        LoggerInterface $logger = null
    )
    {
        $this->locationService = $locationService;
        $this->contentService = $contentService;
        $this->router = $router;
        $this->translationHelper = $translationHelper;
        $this->fieldHelper = $fieldHelper;
        $this->logger = $logger;
    }

    /**
     * Converts internal links (eznode:// and ezobject://) to URLs.
     *
     * @param \DOMDocument $xmlDoc
     *
     * @return string|null
     */
    public function convert( DOMDocument $xmlDoc )
    {
        foreach ( $xmlDoc->getElementsByTagName( "link" ) as $link )
        {
            if ( !$link->hasAttribute( 'custom:file' ) )
            {
                continue;
            }

            $content = null;
            $location = null;

            if ( $link->hasAttribute( 'object_id' ) )
            {
                try
                {
                    $content = $this->contentService->loadContent( $link->getAttribute( 'object_id' ) );
                    $location = $this->locationService->loadLocation( $content->contentInfo->mainLocationId );
                }
                catch ( NotFoundException $e )
                {
                    if ( $this->logger )
                    {
                        $this->logger->warning(
                            "While generating links for xmltext, could not locate " .
                            "Content object with ID " . $link->getAttribute( 'object_id' )
                        );
                    }
                }
                catch ( UnauthorizedException $e )
                {
                    if ( $this->logger )
                    {
                        $this->logger->notice(
                            "While generating links for xmltext, unauthorized to load " .
                            "Content object with ID " . $link->getAttribute( 'object_id' )
                        );
                    }
                }
            }

            if ( $link->hasAttribute( 'node_id' ) )
            {
                try
                {
                    $location = $this->locationService->loadLocation( $link->getAttribute( 'node_id' ) );
                    $content = $this->contentService->loadContent( $location->contentId );
                }
                catch ( NotFoundException $e )
                {
                    if ( $this->logger )
                    {
                        $this->logger->warning(
                            "While generating links for xmltext, could not locate " .
                            "Location with ID " . $link->getAttribute( 'node_id' )
                        );
                    }
                }
                catch ( UnauthorizedException $e )
                {
                    if ( $this->logger )
                    {
                        $this->logger->notice(
                            "While generating links for xmltext, unauthorized to load " .
                            "Location with ID " . $link->getAttribute( 'node_id' )
                        );
                    }
                }
            }

            if ( $content !== null )
            {
                $content = $this->contentService->loadContent( $location->contentId );
                if ( isset( $content->fields['file'] ) && !$this->fieldHelper->isFieldEmpty( $content, 'file' ) )
                {
                    $field = $this->translationHelper->getTranslatedField( $content, 'file' );
                    $url = $this->router->generate( 'ez_content_download_field_id', array( 'contentId' => $content->id, 'fieldId' => $field->id ) );
                }
            }

            if ( empty( $url ) && $location !== null )
            {
                 $link->setAttribute( 'url', $this->router->generate( $location ) );
            }
            else
            {
                $link->setAttribute( 'url', $url );
            }

            if ( $link->hasAttribute( 'anchor_name' ) )
            {
                $link->setAttribute( 'url', $link->getAttribute( 'url' ) . "#" . $link->getAttribute( 'anchor_name' ) );
            }

            // With this we disable the original preconverter
            // (and hopefully all other that touch link element)
            $link->removeAttribute( 'object_id' );
            $link->removeAttribute( 'node_id' );
            $link->removeAttribute( 'anchor_name' );
        }
    }
}
