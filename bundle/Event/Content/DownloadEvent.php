<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Event\Content;

use eZ\Bundle\EzPublishIOBundle\BinaryStreamResponse;
use Symfony\Contracts\EventDispatcher\Event;

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

    public function __construct(int $contentId, int $fieldId, int $versionNo, BinaryStreamResponse $response)
    {
        $this->contentId = $contentId;
        $this->fieldId = $fieldId;
        $this->versionNo = $versionNo;
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
