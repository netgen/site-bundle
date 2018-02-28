<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\DependencyInjection;

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
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig(
            'assetic',
            array(
                'bundles' => array_keys(
                    $container->getParameter('kernel.bundles')
                ),
            )
        );

        $prependConfigs = array(
            'framework/twig.yml' => 'twig',
        );

        foreach ($prependConfigs as $configFile => $prependConfig) {
            $configFile = __DIR__ . '/../Resources/config/' . $configFile;
            $config = Yaml::parse(file_get_contents($configFile));
            $container->prependExtensionConfig($prependConfig, $config);
            $container->addResource(new FileResource($configFile));
        }
    }
}
