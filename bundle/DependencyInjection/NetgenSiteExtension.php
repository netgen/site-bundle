<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\DependencyInjection;

use EzSystems\EzPlatformRichTextBundle\EzPlatformRichTextBundle;
use Netgen\Bundle\LayoutsBundle\NetgenLayoutsBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;
use function file_get_contents;
use function in_array;

class NetgenSiteExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load('parameters.yaml');
        $loader->load('field_types.yaml');
        $loader->load('pagerfanta.yaml');
        $loader->load('templating.yaml');
        $loader->load('menu.yaml');
        $loader->load('event_listeners.yaml');
        $loader->load('matchers.yaml');
        $loader->load('services.yaml');

        $activatedBundles = $container->getParameter('kernel.bundles');

        if (in_array(NetgenLayoutsBundle::class, $activatedBundles, true)) {
            $loader->load('layouts/services.yaml');
        }

        if ($container->getParameter('kernel.debug')) {
            $loader->load('debug.yaml');
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('ngsite.ezfile_storage_path')) {
            $container->setParameter('ngsite.ezfile_storage_path', '/var/site/storage/original');
        }

        $activatedBundles = $container->getParameter('kernel.bundles');

        $prependConfigs = [
            'ezplatform.yaml' => 'ezpublish',
            'ezrichtext.yaml' => 'ezrichtext',
            'framework/twig.yaml' => 'twig',
            'framework/assets.yaml' => 'framework',
        ];

        if (in_array(NetgenLayoutsBundle::class, $activatedBundles, true)) {
            $prependConfigs['layouts/query_types.yaml'] = 'netgen_layouts';
        }

        foreach ($prependConfigs as $configFile => $prependConfig) {
            $configFile = __DIR__ . '/../Resources/config/' . $configFile;
            $config = Yaml::parse(file_get_contents($configFile));
            $container->prependExtensionConfig($prependConfig, $config);
            $container->addResource(new FileResource($configFile));
        }
    }
}
