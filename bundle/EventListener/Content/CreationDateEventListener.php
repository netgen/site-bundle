<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\EventListener\Content;

use DateTimeInterface;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Events\Content\PublishVersionEvent;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\FieldType\DateAndTime\Value as DateAndTimeValue;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function array_key_exists;

final class CreationDateEventListener implements EventSubscriberInterface
{
    public function __construct(
        private ConfigResolverInterface $configResolver,
        private ContentService $contentService,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            PublishVersionEvent::class => ['onPublishVersion', 255],
        ];
    }

    /**
     * Sets the creation date of the content based on a field value.
     */
    public function onPublishVersion(PublishVersionEvent $event): void
    {
        /** @var bool $listenerEnabled */
        $listenerEnabled = $this->configResolver->getParameter('set_creation_date.enabled', 'ngsite');
        if (!$listenerEnabled) {
            return;
        }

        /** @var array<string, string> $fieldConfig */
        $fieldConfig = $this->configResolver->getParameter('set_creation_date.fields', 'ngsite');

        $content = $event->getContent();
        $contentTypeIdentifier = $content->getContentType()->identifier;
        if (!array_key_exists($contentTypeIdentifier, $fieldConfig)) {
            return;
        }

        if (!isset($content->fields[$fieldConfig[$contentTypeIdentifier]])) {
            return;
        }

        $creationDate = $content->getFieldValue($fieldConfig[$contentTypeIdentifier]);
        if (!$creationDate instanceof DateAndTimeValue || !$creationDate->value instanceof DateTimeInterface) {
            return;
        }

        $updateStruct = $this->contentService->newContentMetadataUpdateStruct();
        $updateStruct->publishedDate = $creationDate->value;

        $this->contentService->updateContentMetadata($content->contentInfo, $updateStruct);
    }
}
