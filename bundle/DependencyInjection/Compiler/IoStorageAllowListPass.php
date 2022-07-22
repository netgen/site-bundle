<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\DependencyInjection\Compiler;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function array_merge;
use function array_search;
use function array_values;

class IoStorageAllowListPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $scopes = array_merge(
            [ConfigResolver::SCOPE_DEFAULT],
            $container->getParameter('ezpublish.siteaccess.list'),
        );

        foreach ($scopes as $scope) {
            if ($container->hasParameter("ezsettings.{$scope}.io.file_storage.file_type_blacklist")) {
                $bannedFileTypes = $container->getParameter("ezsettings.{$scope}.io.file_storage.file_type_blacklist");
                $index = array_search('svg', $bannedFileTypes, true);

                if ($index !== false) {
                    unset($bannedFileTypes[$index]);
                    $container->setParameter("ezsettings.{$scope}.io.file_storage.file_type_blacklist", array_values($bannedFileTypes));
                }
            }
        }
    }
}
