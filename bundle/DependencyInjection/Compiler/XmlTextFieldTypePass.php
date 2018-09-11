<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\DependencyInjection\Compiler;

use Netgen\Bundle\SiteBundle\Core\FieldType\XmlText\Converter\EmbedToHtml5;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class XmlTextFieldTypePass implements CompilerPassInterface
{
    /**
     * Overrides EmbedToHtml5 ezxmltext converter with own implementation.
     */
    public function process(ContainerBuilder $container): void
    {
        if ($container->has('ezpublish.fieldType.ezxmltext.converter.embedToHtml5')) {
            $container
                ->findDefinition('ezpublish.fieldType.ezxmltext.converter.embedToHtml5')
                ->setClass(EmbedToHtml5::class)
                ->addMethodCall('setSite', [new Reference('netgen.ezplatform_site.site')]);
        }
    }
}
