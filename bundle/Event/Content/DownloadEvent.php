<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Event\Content;

use eZ\Bundle\EzPublishIOBundle\BinaryStreamResponse;
use Symfony\Contracts\EventDispatcher\Event;

class DownloadEvent extends Event
{
    /**
     * @var int|string
     */
    protected $contentId;

    /**
     * @var int|string
     */
    protected $fieldId;

    /**
     * @var int|string
     */
    protected $versionNo;

    /**
     * @var \eZ\Bundle\EzPublishIOBundle\BinaryStreamResponse
     */
    protected $response;

    /**
     * @param int|string $contentId
     * @param int|string $fieldId
     * @param int|string $versionNo
     * @param \eZ\Bundle\EzPublishIOBundle\BinaryStreamResponse $response
     */
    public function __construct($contentId, $fieldId, $versionNo, BinaryStreamResponse $response)
    {
        $this->contentId = $contentId;
        $this->fieldId = $fieldId;
        $this->versionNo = $versionNo;
        $this->response = $response;
    }

    /**
     * Get field ID.
     *
     * @return int|string
     */
    public function getFieldId()
    {
        return $this->fieldId;
    }

    /**
     * Returns content ID.
     *
     * @return int|string
     */
    public function getContentId()
    {
        return $this->contentId;
    }

    /**
     * Returns version number.
     *
     * @return int|string
     */
    public function getVersionNo()
    {
        return $this->versionNo;
    }

    /**
     * Returns the response.
     *
     * @return \eZ\Bundle\EzPublishIOBundle\BinaryStreamResponse
     */
    public function getResponse(): BinaryStreamResponse
    {
        return $this->response;
    }
}
