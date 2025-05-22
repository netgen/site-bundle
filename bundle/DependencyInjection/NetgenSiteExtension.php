<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\DependencyInjection;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware\ConfigurationProcessor;
use Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware\ContextualizerInterface;
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

use function count;
use function file_get_contents;
use function in_array;

final class NetgenSiteExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->processSemanticConfig($container, $config);

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

    public function getAlias(): string
    {
        return 'ngsite';
    }

    /**
     * Processes semantic config and translates it to container parameters.
     *
     * @param array<string, mixed> $config
     */
    private function processSemanticConfig(ContainerBuilder $container, array $config): void
    {
        $processor = new ConfigurationProcessor($container, 'ngsite');
        $processor->mapConfig(
            $config,
            static function (array $config, string $scope, ContextualizerInterface $c): void {
                if (isset($config['showcase']['rule_priority'])) {
                    $c->setContextualParameter('showcase.rule_priority', $scope, $config['showcase']['rule_priority']);
                }

                if (isset($config['showcase']['rule_group_uuid'])) {
                    $c->setContextualParameter('showcase.rule_group_uuid', $scope, $config['showcase']['rule_group_uuid']);
                }

                if (isset($config['showcase']['blocks']) && count($config['showcase']['blocks']) > 0) {
                    $c->setContextualParameter('showcase.blocks', $scope, $config['showcase']['blocks']);
                }
            },
        );
    }
}
