<?php

namespace Netgen\Bundle\MoreBundle\DependencyInjection\Compiler;

use Netgen\Bundle\MoreBundle\Assetic\Filter\UglifyCssFilter;
use Netgen\Bundle\MoreBundle\Assetic\Filter\UglifyJs2Filter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AsseticFilterPass implements CompilerPassInterface
{
    /**
     * Override uglifyjs2 and uglifycss Assetic filters to disable outputing
     * the input (meaning the entire JS & CSS !!!) when error happens.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->has('assetic.filter.uglifyjs2')) {
            $container
                ->findDefinition('assetic.filter.uglifyjs2')
                ->setClass(UglifyJs2Filter::class);
        }

        if ($container->has('assetic.filter.uglifycss')) {
            $container
                ->findDefinition('assetic.filter.uglifycss')
                ->setClass(UglifyCssFilter::class);
        }
    }
}
