<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\XmlText\Converter;

use DOMDocument;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use eZ\Publish\Core\FieldType\XmlText\Converter;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Routing\RouterInterface;

class EzLinkDirectDownload implements Converter
{
    /**
     * @var \Netgen\EzPlatformSiteApi\API\LoadService
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
     * @param \Netgen\EzPlatformSiteApi\API\LoadService $loadService
     * @param \Symfony\Component\Routing\RouterInterface $router
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        LoadService $loadService,
        RouterInterface $router,
        LoggerInterface $logger = null
    ) {
        $this->loadService = $loadService;
        $this->router = $router;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Converts internal links (eznode:// and ezobject://) to URLs.
     *
     * @param \DOMDocument $xmlDoc
     *
     * @return string|null
     */
    public function convert(DOMDocument $xmlDoc)
    {
        foreach ($xmlDoc->getElementsByTagName('link') as $link) {
            /** @var \DOMElement $link */
            if (!$link->hasAttribute('custom:file')) {
                continue;
            }

            $content = null;
            $location = null;

            if ($link->hasAttribute('object_id')) {
                try {
                    $content = $this->loadService->loadContent($link->getAttribute('object_id'));
                    $location = $content->mainLocation;
                } catch (NotFoundException $e) {
                    $this->logger->warning(
                        'While generating links for xmltext, could not locate ' .
                        'Content object with ID ' . $link->getAttribute('object_id')
                    );
                } catch (UnauthorizedException $e) {
                    $this->logger->notice(
                        'While generating links for xmltext, unauthorized to load ' .
                        'Content object with ID ' . $link->getAttribute('object_id')
                    );
                }
            }

            if ($link->hasAttribute('node_id')) {
                try {
                    $location = $this->loadService->loadLocation($link->getAttribute('node_id'));
                    $content = $location->content;
                } catch (NotFoundException $e) {
                    $this->logger->warning(
                        'While generating links for xmltext, could not locate ' .
                        'Location with ID ' . $link->getAttribute('node_id')
                    );
                } catch (UnauthorizedException $e) {
                    $this->logger->notice(
                        'While generating links for xmltext, unauthorized to load ' .
                        'Location with ID ' . $link->getAttribute('node_id')
                    );
                }
            }

            if ($content !== null) {
                if ($content->hasField('file')) {
                    $field = $content->getField('file');
                    if (!$field->isEmpty()) {
                        $url = $this->router->generate('ngmore_download', array('contentId' => $content->id, 'fieldId' => $field->id, 'isInline' => $link->hasAttribute('custom:inline')));
                    }
                }
            }

            if (empty($url) && $location !== null) {
                $link->setAttribute('url', $this->router->generate($location));
            } elseif (!empty($url)) {
                $link->setAttribute('url', $url);
            } else {
                $link->setAttribute('url', '');
            }

            if ($link->hasAttribute('anchor_name')) {
                $link->setAttribute('url', $link->getAttribute('url') . '#' . $link->getAttribute('anchor_name'));
            }

            // With this we disable the original preconverter
            // (and hopefully all other that touch link element)
            $link->removeAttribute('object_id');
            $link->removeAttribute('node_id');
            $link->removeAttribute('anchor_name');
        }
    }
}
