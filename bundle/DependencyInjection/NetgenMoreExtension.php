<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\DependencyInjection;

use Netgen\Bundle\BlockManagerBundle\NetgenBlockManagerBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

class NetgenMoreExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load('field_types.yml');
        $loader->load('pagerfanta.yml');
        $loader->load('templating.yml');
        $loader->load('menu.yml');
        $loader->load('event_listeners.yml');
        $loader->load('matchers.yml');
        $loader->load('services.yml');

        $activatedBundles = $container->getParameter('kernel.bundles');

        if (in_array(NetgenBlockManagerBundle::class, $activatedBundles, true)) {
            $loader->load('layouts/services.yml');
        }

        if ($container->getParameter('kernel.debug')) {
            $loader->load('debug.yml');
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        $activatedBundles = $container->getParameter('kernel.bundles');

        $prependConfigs = [
            'framework/twig.yml' => 'twig',
            'framework/assets.yml' => 'framework',
        ];

        if (in_array(NetgenBlockManagerBundle::class, $activatedBundles, true)) {
            $prependConfigs['layouts/query_types.yml'] = 'netgen_block_manager';
        }

        foreach ($prependConfigs as $configFile => $prependConfig) {
            $configFile = __DIR__ . '/../Resources/config/' . $configFile;
            $config = Yaml::parse(file_get_contents($configFile));
            $container->prependExtensionConfig($prependConfig, $config);
            $container->addResource(new FileResource($configFile));
        }
    }
}
