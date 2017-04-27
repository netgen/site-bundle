<?php

namespace Netgen\Bundle\MoreBundle\DependencyInjection\Compiler;

use Netgen\Bundle\MoreBundle\Core\FieldType\XmlText\Converter\EmbedToHtml5;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class XmlTextFieldTypePass implements CompilerPassInterface
{
    /**
     * Compiler pass override ezxmltext field type.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->has('ezpublish.fieldType.ezxmltext.converter.embedToHtml5')) {
            $container
                ->findDefinition('ezpublish.fieldType.ezxmltext.converter.embedToHtml5')
                ->setClass(EmbedToHtml5::class);
        }
    }
}
