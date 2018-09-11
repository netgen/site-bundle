<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Event\Content;

use eZ\Bundle\EzPublishIOBundle\BinaryStreamResponse;
use Symfony\Component\EventDispatcher\Event;

class DownloadEvent extends Event
{
    /**
     * @var int
     */
    protected $contentId;

    /**
     * @var int
     */
    protected $fieldId;

    /**
     * @var int
     */
    protected $versionNo;

    /**
     * @var \eZ\Bundle\EzPublishIOBundle\BinaryStreamResponse
     */
    protected $response;

    /**
     * Constructor.
     *
     * @param int $contentId
     * @param int $fieldId
     * @param int $versionNo
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
     * @return int
     */
    public function getFieldId()
    {
        return $this->fieldId;
    }

    /**
     * Returns content ID.
     *
     * @return int
     */
    public function getContentId()
    {
        return $this->contentId;
    }

    /**
     * Returns version number.
     *
     * @return int
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
