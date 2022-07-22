<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\DependencyInjection\Compiler;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\ConfigResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function array_merge;
use function array_search;
use function array_values;

class IoStorageAllowListPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $scopes = array_merge(
            [ConfigResolver::SCOPE_DEFAULT],
            $container->getParameter('ibexa.site_access.list'),
        );

        foreach ($scopes as $scope) {
            if ($container->hasParameter("ibexa.site_access.config.{$scope}.io.file_storage.file_type_blacklist")) {
                $bannedFileTypes = $container->getParameter("ibexa.site_access.config.{$scope}.io.file_storage.file_type_blacklist");
                $index = array_search('svg', $bannedFileTypes, true);

                if ($index !== false) {
                    unset($bannedFileTypes[$index]);
                    $container->setParameter("ibexa.site_access.config.{$scope}.io.file_storage.file_type_blacklist", array_values($bannedFileTypes));
                }
            }
        }
    }
}
