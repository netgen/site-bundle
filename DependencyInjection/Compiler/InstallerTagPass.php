<?php

namespace Netgen\Bundle\MoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiles services tagged as ezplatform.installer to %ezplatform.installers%.
 */
class InstallerTagPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('ngmore.command.install')) {
            return;
        }

        $installCommandDef = $container->findDefinition('ngmore.command.install');
        $installers = [];

        foreach ($container->findTaggedServiceIds('ezplatform.installer') as $id => $tags) {
            foreach ($tags as $tag) {
                if (!isset($tag['type'])) {
                    throw new \LogicException("ezplatform.installer service tag needs a 'type' attribute to identify the installer. None given for $id.");
                }

                $installers[$tag['type']] = new Reference($id);
            }
        }

        $installCommandDef->replaceArgument(1, $installers);
    }
}
