<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Core\MVC\Symfony\FieldType\BinaryBase;

use eZ\Publish\SPI\FieldType\BinaryBase\PathGenerator;
use eZ\Publish\SPI\Persistence\Content\Field;
use eZ\Publish\SPI\Persistence\Content\VersionInfo;
use Symfony\Component\Routing\RouterInterface;

/**
 * Generates the path to the file stored in provided field.
 *
 * Overrides the base generator to allow generating the link with
 * Netgen More specific route instead of the built in one.
 */
class ContentDownloadUrlGenerator extends PathGenerator
{
    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    protected $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function getStoragePathForField(Field $field, VersionInfo $versionInfo): string
    {
        return $this->router->generate(
            'ngmore_download',
            [
                'contentId' => $versionInfo->contentInfo->id,
                'fieldId' => $field->id,
            ]
        );
    }
}
