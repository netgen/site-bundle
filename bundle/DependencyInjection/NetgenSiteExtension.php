<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\DependencyInjection;

use Netgen\Bundle\LayoutsBundle\NetgenLayoutsBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

use function file_get_contents;
use function in_array;

final class NetgenSiteExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $locator = new FileLocator(__DIR__ . '/../Resources/config');
        $loader = new DelegatingLoader(
            new LoaderResolver(
                [
                    new GlobFileLoader($container, $locator),
                    new YamlFileLoader($container, $locator),
                ],
            ),
        );

        $loader->load('parameters.yaml');
        $loader->load('pagerfanta.yaml');
        $loader->load('templating.yaml');
        $loader->load('menu.yaml');
        $loader->load('event_listeners.yaml');
        $loader->load('matchers.yaml');
        $loader->load('info_collection.yaml');
        $loader->load('services.yaml');
        $loader->load('services/**/*.yaml', 'glob');

        /** @var array<class-string> $activatedBundles */
        $activatedBundles = $container->getParameter('kernel.bundles');

        if (in_array(NetgenLayoutsBundle::class, $activatedBundles, true)) {
            $loader->load('layouts/services.yaml');
        }

        if ($container->getParameter('kernel.debug') === true) {
            $loader->load('debug.yaml');
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('ngsite.ibexa_file_storage_path')) {
            $container->setParameter('ngsite.ibexa_file_storage_path', '/var/site/storage/original');
        }

        /** @var array<class-string> $activatedBundles */
        $activatedBundles = $container->getParameter('kernel.bundles');

        $prependConfigs = [
            'ibexa.yaml' => 'ibexa',
            'ezrichtext.yaml' => 'ibexa_fieldtype_richtext',
            'framework/twig.yaml' => 'twig',
            'framework/assets.yaml' => 'framework',
        ];

        if (in_array(NetgenLayoutsBundle::class, $activatedBundles, true)) {
            $prependConfigs['layouts/query_types.yaml'] = 'netgen_layouts';
        }

        foreach ($prependConfigs as $configFile => $prependConfig) {
            $configFile = __DIR__ . '/../Resources/config/' . $configFile;
            $config = Yaml::parse((string) file_get_contents($configFile));
            $container->prependExtensionConfig($prependConfig, $config);
            $container->addResource(new FileResource($configFile));
        }
    }
}
