<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Layouts\Block\Plugin;

use Netgen\BlockManager\Block\BlockDefinition\BlockDefinitionHandlerInterface;
use Netgen\BlockManager\Block\BlockDefinition\Handler\Plugin;
use Netgen\BlockManager\Parameters\ParameterBuilderInterface;
use Netgen\BlockManager\Parameters\ParameterType;

class VerticalWhitespacePlugin extends Plugin
{
    public static function getExtendedHandler()
    {
        return [BlockDefinitionHandlerInterface::class];
    }

    public function buildParameters(ParameterBuilderInterface $builder): void
    {
        $designGroup = [self::GROUP_DESIGN];

        $builder->add(
            'vertical_whitespace:enabled',
            ParameterType\Compound\BooleanType::class,
            [
                'default_value' => false,
                'label' => 'block.plugin.vertical_whitespace.enabled',
                'groups' => $designGroup,
            ]
        );

        $builder->get('vertical_whitespace:enabled')->add(
            'vertical_whitespace:position',
            ParameterType\ChoiceType::class,
            [
                'default_value' => 'both',
                'label' => 'block.plugin.vertical_whitespace.position',
                'options' => [
                    'block.plugin.vertical_whitespace.top_and_bottom' => 'both',
                    'block.plugin.vertical_whitespace.only_top' => 'top',
                    'block.plugin.vertical_whitespace.only_bottom' => 'bottom',
                ],
                'groups' => $designGroup,
            ]
        );

        $builder->get('vertical_whitespace:enabled')->add(
            'vertical_whitespace:size',
            ParameterType\ChoiceType::class,
            [
                'default_value' => 'md',
                'label' => 'block.plugin.vertical_whitespace.size',
                'options' => [
                    'block.plugin.vertical_whitespace.large' => 'lg',
                    'block.plugin.vertical_whitespace.medium' => 'md',
                    'block.plugin.vertical_whitespace.small' => 'sm',
                ],
                'groups' => $designGroup,
            ]
        );
    }
}
