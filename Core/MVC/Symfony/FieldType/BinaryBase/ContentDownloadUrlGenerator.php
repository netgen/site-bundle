<?php

namespace Netgen\Bundle\MoreBundle\Core\MVC\Symfony\FieldType\BinaryBase;

use eZ\Publish\SPI\FieldType\BinaryBase\PathGenerator;
use eZ\Publish\SPI\Persistence\Content\Field;
use eZ\Publish\SPI\Persistence\Content\VersionInfo;
use Symfony\Component\Routing\RouterInterface;

class ContentDownloadUrlGenerator extends PathGenerator
{
    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    protected $router;

    /**
     * Constructor.
     *
     * @param \Symfony\Component\Routing\RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param \eZ\Publish\SPI\Persistence\Content\Field $field
     * @param \eZ\Publish\SPI\Persistence\Content\VersionInfo $versionInfo
     *
     * @return string
     */
    public function getStoragePathForField(Field $field, VersionInfo $versionInfo)
    {
        return $this->router->generate(
            'ngmore_download',
            array(
                'contentId' => $versionInfo->contentInfo->id,
                'fieldId' => $field->id,
            )
        );
    }
}
