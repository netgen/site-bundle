<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\DependencyInjection\Compiler;

use Netgen\Bundle\MoreBundle\Core\FieldType\XmlText\Converter\EmbedToHtml5;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class XmlTextFieldTypePass implements CompilerPassInterface
{
    /**
     * Overrides EmbedToHtml5 ezxmltext converter with own implementation.
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->has('ezpublish.fieldType.ezxmltext.converter.embedToHtml5')) {
            $container
                ->findDefinition('ezpublish.fieldType.ezxmltext.converter.embedToHtml5')
                ->setClass(EmbedToHtml5::class)
                ->addMethodCall('setSite', array(new Reference('netgen.ezplatform_site.site')));
        }
    }
}
