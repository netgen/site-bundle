<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Event\Content;

use Ibexa\Bundle\IO\BinaryStreamResponse;
use Symfony\Contracts\EventDispatcher\Event;

final class DownloadEvent extends Event
{
    public function __construct(
        public private(set) int $contentId,
        public private(set) int $fieldId,
        public private(set) int $versionNo,
        public private(set) BinaryStreamResponse $response,
    ) {}
}
