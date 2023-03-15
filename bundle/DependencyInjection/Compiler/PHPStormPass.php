<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;

use function json_encode;
use function realpath;
use function str_replace;
use function trim;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

final class PHPStormPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->getParameter('kernel.debug') === false) {
            return;
        }

        if (!$container->hasParameter('ibexa.design.templates.path_map')) {
            return;
        }

        $pathConfig = [];

        /** @var string $projectDir */
        $projectDir = $container->getParameter('kernel.project_dir');
        $twigConfigPath = (string) realpath($projectDir);

        /** @var array<string, string[]> $pathMap */
        $pathMap = $container->getParameter('ibexa.design.templates.path_map');

        foreach ($pathMap as $theme => $paths) {
            foreach ($paths as $path) {
                if ($theme !== '_override') {
                    $pathConfig[] = [
                        'namespace' => $theme,
                        'path' => $this->makeTwigPathRelative($path, $twigConfigPath),
                    ];
                }

                $pathConfig[] = [
                    'namespace' => 'ibexadesign',
                    'path' => $this->makeTwigPathRelative($path, $twigConfigPath),
                ];
            }
        }

        (new Filesystem())->dumpFile(
            $twigConfigPath . '/ide-twig.json',
            json_encode(
                [
                    'namespaces' => $pathConfig,
                ],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
            ),
        );
    }

    /**
     * Converts absolute $path to a path relative to ide-twig.json config file.
     */
    private function makeTwigPathRelative(string $path, string $configPath): string
    {
        return trim(str_replace($configPath, '', $path), '/');
    }
}
