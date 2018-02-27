<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\DependencyInjection\Compiler;

use Netgen\Bundle\MoreBundle\Core\FieldType\RelationList\Type;
use Netgen\Bundle\MoreBundle\Core\Persistence\Legacy\Content\FieldValue\Converter\RelationListConverter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RelationListFieldTypePass implements CompilerPassInterface
{
    /**
     * Overrides ezrelationlist field type with own implementations.
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->has('ezpublish.fieldType.ezobjectrelationlist')) {
            $container
                ->findDefinition('ezpublish.fieldType.ezobjectrelationlist')
                ->setClass(Type::class);
        }

        if ($container->has('ezpublish.fieldType.ezobjectrelationlist.converter')) {
            $container
                ->findDefinition('ezpublish.fieldType.ezobjectrelationlist.converter')
                ->setClass(RelationListConverter::class);
        }
    }
}
