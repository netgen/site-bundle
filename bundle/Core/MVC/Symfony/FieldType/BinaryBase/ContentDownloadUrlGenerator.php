<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Core\MVC\Symfony\FieldType\BinaryBase;

use eZ\Publish\SPI\FieldType\BinaryBase\PathGenerator;
use eZ\Publish\SPI\Persistence\Content\Field;
use eZ\Publish\SPI\Persistence\Content\VersionInfo;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Generates the path to the file stored in provided field.
 *
 * Overrides the base generator to allow generating the link with
 * Netgen Site specific route instead of the built in one.
 */
class ContentDownloadUrlGenerator extends PathGenerator
{
    /**
     * @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface
     */
    protected $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function getStoragePathForField(Field $field, VersionInfo $versionInfo): string
    {
        return $this->urlGenerator->generate(
            'ngsite_download',
            [
                'contentId' => $versionInfo->contentInfo->id,
                'fieldId' => $field->id,
            ]
        );
    }
}
