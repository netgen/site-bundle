<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Core\MVC\Symfony\FieldType\BinaryBase;

use eZ\Publish\SPI\FieldType\BinaryBase\PathGenerator;
use eZ\Publish\SPI\FieldType\BinaryBase\RouteAwarePathGenerator;
use eZ\Publish\SPI\Persistence\Content\Field;
use eZ\Publish\SPI\Persistence\Content\VersionInfo;
use Symfony\Component\Routing\RouterInterface;

/**
 * Generates the path to the file stored in provided field.
 *
 * Overrides the base generator to allow generating the link with
 * Netgen Site specific route instead of the built in one.
 */
class ContentDownloadUrlGenerator extends PathGenerator implements RouteAwarePathGenerator
{
    protected RouterInterface $router;

    protected string $route = 'ngsite_download';

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function getStoragePathForField(Field $field, VersionInfo $versionInfo): string
    {
        return $this->generate($this->route, $this->getParameters($field, $versionInfo));
    }

    public function generate(string $route, ?array $parameters = []): string
    {
        return $this->router->generate($route, $parameters ?? []);
    }

    public function getRoute(Field $field, VersionInfo $versionInfo): string
    {
        return $this->route;
    }

    public function getParameters(Field $field, VersionInfo $versionInfo): array
    {
        return [
            'contentId' => $versionInfo->contentInfo->id,
            'fieldId' => $field->id,
        ];
    }
}
