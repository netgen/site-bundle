<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\Core\MVC\Symfony\View\ContentView;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use Psr\Log\LoggerInterface;

class EmbedViewController extends Controller
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct( LoggerInterface $logger = null )
    {
        $this->logger = $logger;
    }

    /**
     * Action for viewing embedded content with image content type identifier
     *
     * @param \eZ\Publish\Core\MVC\Symfony\View\ContentView $view
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function embedImage( ContentView $view )
    {
        $fieldHelper = $this->container->get( 'ezpublish.field_helper' );
        $translationHelper = $this->container->get( 'ezpublish.translation_helper' );

        $parameters = $view->getParameters();
        $targetLink = !empty( $parameters['objectParameters']['href'] ) ? trim( $parameters['objectParameters']['href'] ) : null;
        if ( !empty( $targetLink ) )
        {
            if ( !empty( $parameters['objectParameters']['link_direct_download'] ) )
            {
                if ( stripos( $targetLink, 'eznode://' ) === 0 )
                {
                    $locationId = (int)substr( $targetLink, 9 );

                    try
                    {
                        $location = $this->getRepository()->getLocationService()->loadLocation( $locationId );
                        $content = $this->getRepository()->getContentService()->loadContent( $location->contentId );
                    }
                    catch ( NotFoundException $e )
                    {
                        $targetLink = null;
                        if ( $this->logger )
                        {
                            $this->logger->error(
                                'Tried to generate link to non existing location #' . $locationId
                            );
                        }
                    }
                    catch ( UnauthorizedException $e )
                    {
                        $targetLink = null;
                        if ( $this->logger )
                        {
                            $this->logger->error(
                                'Tried to generate link to location #' . $locationId . ' without read rights'
                            );
                        }
                    }
                }
                else if ( stripos( $targetLink, 'ezobject://' ) === 0 )
                {
                    $linkedContentId = (int)substr( $targetLink, 11 );

                    try
                    {
                        $content = $this->getRepository()->getContentService()->loadContent( $linkedContentId );
                    }
                    catch ( NotFoundException $e )
                    {
                        $targetLink = null;
                        if ( $this->logger )
                        {
                            $this->logger->error(
                                'Tried to generate link to non existing content #' . $linkedContentId
                            );
                        }
                    }
                    catch ( UnauthorizedException $e )
                    {
                        $targetLink = null;
                        if ( $this->logger )
                        {
                            $this->logger->error(
                                'Tried to generate link to content #' . $linkedContentId . ' without read rights'
                            );
                        }
                    }
                }

                if ( !empty( $content ) )
                {
                    $fieldName = null;
                    if ( isset( $content->fields['file'] ) && !$fieldHelper->isFieldEmpty( $content, 'file' ) )
                    {
                        $fieldName = 'file';
                    }
                    else if ( isset( $content->fields['image'] ) && !$fieldHelper->isFieldEmpty( $content, 'image' ) )
                    {
                        $fieldName = 'image';
                    }

                    if ( $fieldName !== null )
                    {
                        $field = $translationHelper->getTranslatedField( $content, $fieldName );
                        $targetLink = $this->generateUrl(
                            'ngmore_download',
                            array(
                                'contentId' => $content->id,
                                'fieldId' => $field->id
                            )
                        );
                    }
                }
            }

            if ( stripos( $targetLink, 'eznode://' ) === 0 )
            {
                $targetLink = $this->container->get( 'router' )->generate( $location );
            }
            else if ( stripos( $targetLink, 'ezobject://' ) === 0 )
            {
                $targetLink = $this->container->get( 'router' )->generate( $content );
            }
        }

        $view->addParameters(
            array(
                'link_href' => $targetLink
            )
        );

        return $view;
    }
}
