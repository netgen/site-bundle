<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\DependencyInjection\Compiler;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\ConfigResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class XslRegisterPass implements CompilerPassInterface
{
    /**
     * Registers various Docbook XSL files as custom XSL stylesheets for ezrichtext field type.
     */
    public function process(ContainerBuilder $container): void
    {
        /** @var string[] $siteAccessList */
        $siteAccessList = $container->getParameter('ibexa.site_access.list');
        $scopes = [ConfigResolver::SCOPE_DEFAULT, ...$siteAccessList];

        foreach ($scopes as $scope) {
            if ($container->hasParameter('ibexa.site_access.config' . $scope . 'fieldtypes.ezrichtext.output_custom_xsl')) {
                /** @var array<int, array<string, mixed>> $xslConfig */
                $xslConfig = $container->getParameter('ibexa.site_access.config' . $scope . 'fieldtypes.ezrichtext.output_custom_xsl');
                $xslConfig[] = ['path' => __DIR__ . '/../../Resources/docbook/output/core.xsl', 'priority' => 5000];
                $container->setParameter('ibexa.site_access.config' . $scope . 'fieldtypes.ezrichtext.output_custom_xsl', $xslConfig);
            }

            if ($container->hasParameter('ibexa.site_access.config' . $scope . 'fieldtypes.ezrichtext.edit_custom_xsl')) {
                /** @var array<int, array<string, mixed>> $xslConfig */
                $xslConfig = $container->getParameter('ibexa.site_access.config' . $scope . 'fieldtypes.ezrichtext.edit_custom_xsl');
                $xslConfig[] = ['path' => __DIR__ . '/../../Resources/docbook/edit/core.xsl', 'priority' => 5000];
                $container->setParameter('ibexa.site_access.config' . $scope . 'fieldtypes.ezrichtext.edit_custom_xsl', $xslConfig);
            }
        }
    }
}
