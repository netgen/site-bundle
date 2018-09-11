<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\DependencyInjection\Compiler;

use Netgen\Bundle\SiteBundle\Core\FieldType\RelationList\Type;
use Netgen\Bundle\SiteBundle\Core\Persistence\Legacy\Content\FieldValue\Converter\RelationListConverter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RelationListFieldTypePass implements CompilerPassInterface
{
    /**
     * Overrides ezrelationlist field type with own implementations.
     */
    public function process(ContainerBuilder $container): void
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
