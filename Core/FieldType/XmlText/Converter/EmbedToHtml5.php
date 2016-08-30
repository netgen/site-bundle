<?php

namespace Netgen\Bundle\MoreBundle\Core\FieldType\XmlText\Converter;

use eZ\Publish\Core\FieldType\XmlText\Converter\EmbedToHtml5 as BaseEmbedToHtml5;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\Base\Exceptions\UnauthorizedException;
use eZ\Publish\API\Repository\Values\Content\VersionInfo as APIVersionInfo;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use eZ\Publish\API\Repository\Exceptions\NotFoundException as APINotFoundException;
use DOMDocument;

/**
 * Converts embedded elements from internal XmlText representation to HTML5.
 */
class EmbedToHtml5 extends BaseEmbedToHtml5
{
    /**
     * Process embed tags for a single tag type (embed or embed-inline).
     *
     * @param \DOMDocument $xmlDoc
     * @param $tagName string name of the tag to extract
     */
    protected function processTag(DOMDocument $xmlDoc, $tagName)
    {
        /** @var $embed \DOMElement */
        foreach ($xmlDoc->getElementsByTagName($tagName) as $embed) {
            if (!$view = $embed->getAttribute('view')) {
                $view = $tagName;
            }

            $embedContent = null;
            $parameters = $this->getParameters($embed);

            if ($contentId = $embed->getAttribute('object_id')) {
                try {
                    /** @var \eZ\Publish\API\Repository\Values\Content\Content $content */
                    $content = $this->repository->sudo(
                        function (Repository $repository) use ($contentId) {
                            return $repository->getContentService()->loadContent($contentId);
                        }
                    );

                    if (
                        !$this->repository->canUser('content', 'read', $content)
                        && !$this->repository->canUser('content', 'view_embed', $content)
                    ) {
                        throw new UnauthorizedException('content', 'read', array('contentId' => $contentId));
                    }

                    // Check published status of the Content
                    if (
                        $content->getVersionInfo()->status !== APIVersionInfo::STATUS_PUBLISHED
                        && !$this->repository->canUser('content', 'versionread', $content)
                    ) {
                        throw new UnauthorizedException('content', 'versionread', array('contentId' => $contentId));
                    }

                    $embedContent = $this->fragmentHandler->render(
                        new ControllerReference(
                            'ng_content:embedAction',
                            array(
                                'contentId' => $contentId,
                                'viewType' => $view,
                                'layout' => false,
                                'params' => $parameters,
                            )
                        )
                    );
                } catch (APINotFoundException $e) {
                    if ($this->logger) {
                        $this->logger->error(
                            'While generating embed for xmltext, could not locate ' .
                            'Content object with ID ' . $contentId
                        );
                    }
                }
            } elseif ($locationId = $embed->getAttribute('node_id')) {
                try {
                    /** @var \eZ\Publish\API\Repository\Values\Content\Location $location */
                    $location = $this->repository->sudo(
                        function (Repository $repository) use ($locationId) {
                            return $repository->getLocationService()->loadLocation($locationId);
                        }
                    );

                    if (
                        !$this->repository->canUser('content', 'read', $location->getContentInfo(), $location)
                        && !$this->repository->canUser('content', 'view_embed', $location->getContentInfo(), $location)
                    ) {
                        throw new UnauthorizedException('content', 'read', array('locationId' => $location->id));
                    }

                    $embedContent = $this->fragmentHandler->render(
                        new ControllerReference(
                            'ng_content:embedAction',
                            array(
                                'contentId' => $location->getContentInfo()->id,
                                'locationId' => $location->id,
                                'viewType' => $view,
                                'layout' => false,
                                'params' => $parameters,
                            )
                        )
                    );
                } catch (APINotFoundException $e) {
                    if ($this->logger) {
                        $this->logger->error(
                            'While generating embed for xmltext, could not locate ' .
                            'Location with ID ' . $locationId
                        );
                    }
                }
            }

            if ($embedContent === null) {
                // Remove empty embed
                $embed->parentNode->removeChild($embed);
            } else {
                $embed->appendChild($xmlDoc->createCDATASection($embedContent));
            }
        }
    }
}
