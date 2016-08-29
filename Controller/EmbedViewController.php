<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use Netgen\EzPlatformSite\API\LoadService;
use Symfony\Component\Routing\RouterInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class EmbedViewController extends Controller
{
    /**
     * @var \Netgen\EzPlatformSite\API\LoadService
     */
    protected $loadService;

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
     * @param \Netgen\EzPlatformSite\API\LoadService $loadService
     * @param \Symfony\Component\Routing\RouterInterface $router
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(LoadService $loadService, RouterInterface $router, LoggerInterface $logger = null)
    {
        $this->loadService = $loadService;
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
            if (!empty($parameters['objectParameters']['link_direct_download'])) {
                if (stripos($targetLink, 'eznode://') === 0) {
                    $locationId = (int)substr($targetLink, 9);

                    try {
                        $location = $this->loadService->loadLocation($locationId);
                        $content = $this->loadService->loadContent($location->contentId);
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
                    $linkedContentId = (int)substr($targetLink, 11);

                    try {
                        $content = $this->loadService->loadContent($linkedContentId);
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

                if (!empty($content)) {
                    $fieldName = null;
                    if ($content->hasField('file') && !$content->getField('file')->isEmpty()) {
                        $fieldName = 'file';
                    } elseif ($content->hasField('image') && !$content->getField('image')->isEmpty()) {
                        $fieldName = 'image';
                    }

                    if ($fieldName !== null) {
                        $targetLink = $this->generateUrl(
                            'ngmore_download',
                            array(
                                'contentId' => $content->id,
                                'fieldId' => $content->getField($fieldName)->id,
                            )
                        );
                    }
                }
            }

            if (stripos($targetLink, 'eznode://') === 0) {
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
