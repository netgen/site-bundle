<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Event\Content;

use eZ\Bundle\EzPublishIOBundle\BinaryStreamResponse;
use Symfony\Component\EventDispatcher\Event;

class DownloadEvent extends Event
{
    protected int $contentId;

    protected int $fieldId;

    protected int $versionNo;

    protected BinaryStreamResponse $response;

    /**
     * @param int|string $contentId
     * @param int|string $fieldId
     * @param int|string $versionNo
     */
    public function __construct($contentId, $fieldId, $versionNo, BinaryStreamResponse $response)
    {
        $this->contentId = (int) $contentId;
        $this->fieldId = (int) $fieldId;
        $this->versionNo = (int) $versionNo;
        $this->response = $response;
    }

    /**
     * Get field ID.
     */
    public function getFieldId(): int
    {
        return $this->fieldId;
    }

    /**
     * Returns content ID.
     */
    public function getContentId(): int
    {
        return $this->contentId;
    }

    /**
     * Returns version number.
     */
    public function getVersionNo(): int
    {
        return $this->versionNo;
    }

    /**
     * Returns the response.
     */
    public function getResponse(): BinaryStreamResponse
    {
        return $this->response;
    }
}
