<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Templating\Twig\Extension;

use eZ\Publish\Core\FieldType\Image\Value as ImageValue;
use eZ\Publish\SPI\Variation\VariationHandler;
use Netgen\Bundle\RemoteMediaBundle\Core\FieldType\RemoteMedia\Value as RemoteImageValue;
use Netgen\Bundle\RemoteMediaBundle\RemoteMedia\RemoteMediaProvider;
use Netgen\EzPlatformSiteApi\API\Values\Content;

class RemoteMediaRuntime
{
    protected VariationHandler $imageVariationService;

    protected RemoteMediaProvider $remoteMediaProvider;

    public function __construct(VariationHandler $imageVariationService, RemoteMediaProvider $remoteMediaProvider)
    {
        $this->imageVariationService = $imageVariationService;
        $this->remoteMediaProvider = $remoteMediaProvider;
    }

    public function getImageUrl(Content $content, string $fieldIdentifier, string $alias): ?string
    {
        $field = $content->getField($fieldIdentifier);

        if ($field->value instanceof RemoteImageValue) {
            return $this->remoteMediaProvider->buildVariation($field->value, $content->contentInfo->contentTypeIdentifier, $alias)->url;
        }

        if ($field->value instanceof ImageValue) {
            return $this->imageVariationService->getVariation($field->innerField, $content->innerVersionInfo, $alias)->uri;
        }

        return '/';
    }
}
