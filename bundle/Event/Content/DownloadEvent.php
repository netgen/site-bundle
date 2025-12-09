<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Event\Content;

use Ibexa\Bundle\IO\BinaryStreamResponse;
use Symfony\Contracts\EventDispatcher\Event;

final class DownloadEvent extends Event
{
    public function __construct(
        private int $contentId,
        private int $fieldId,
        private int $versionNo,
        private BinaryStreamResponse $response,
    ) {}

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
