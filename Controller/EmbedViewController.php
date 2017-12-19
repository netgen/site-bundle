<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use Netgen\Bundle\EzPlatformSiteApiBundle\Controller\Controller;
use Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Routing\RouterInterface;

class EmbedViewController extends Controller
{
    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    protected $router;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param \Symfony\Component\Routing\RouterInterface $router
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(RouterInterface $router, LoggerInterface $logger = null)
    {
        $this->router = $router;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Action for viewing embedded content with image content type identifier.
     *
     * @param \Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView $view
     *
     * @return \Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView
     */
    public function embedImage(ContentView $view)
    {
        $parameters = $view->getParameters();
        $targetLink = !empty($parameters['objectParameters']['href']) ? trim($parameters['objectParameters']['href']) : null;

        if (!empty($targetLink)) {
            if (stripos($targetLink, 'eznode://') === 0) {
                $locationId = (int) substr($targetLink, 9);

                try {
                    $location = $this->getSite()->getLoadService()->loadLocation($locationId);
                    $content = $location->content;
                } catch (NotFoundException $e) {
                    $targetLink = null;
                    $this->logger->error(
                        'Tried to generate link to non existing location #' . $locationId
                    );
                } catch (UnauthorizedException $e) {
                    $targetLink = null;
                    $this->logger->error(
                        'Tried to generate link to location #' . $locationId . ' without read rights'
                    );
                }
            } elseif (stripos($targetLink, 'ezobject://') === 0) {
                $linkedContentId = (int) substr($targetLink, 11);

                try {
                    $content = $this->getSite()->getLoadService()->loadContent($linkedContentId);
                } catch (NotFoundException $e) {
                    $targetLink = null;
                    $this->logger->error(
                        'Tried to generate link to non existing content #' . $linkedContentId
                    );
                } catch (UnauthorizedException $e) {
                    $targetLink = null;
                    $this->logger->error(
                        'Tried to generate link to content #' . $linkedContentId . ' without read rights'
                    );
                }
            }

            $directDownloadLink = null;
            if (!empty($content) && !empty($parameters['objectParameters']['link_direct_download'])) {
                $fieldName = null;
                if ($content->hasField('file') && !$content->getField('file')->isEmpty()) {
                    $fieldName = 'file';
                } elseif ($content->hasField('image') && !$content->getField('image')->isEmpty()) {
                    $fieldName = 'image';
                }

                if ($fieldName !== null) {
                    $directDownloadLink = $this->generateUrl(
                        'ngmore_download',
                        array(
                            'contentId' => $content->id,
                            'fieldId' => $content->getField($fieldName)->id,
                        )
                    );
                }
            }

            if ($directDownloadLink !== null) {
                $targetLink = $directDownloadLink;
            } elseif (stripos($targetLink, 'eznode://') === 0) {
                $targetLink = $this->router->generate($location);
            } elseif (stripos($targetLink, 'ezobject://') === 0) {
                $targetLink = $this->router->generate($content);
            }
        }

        $view->addParameters(
            array(
                'link_href' => $targetLink,
            )
        );

        return $view;
    }
}
