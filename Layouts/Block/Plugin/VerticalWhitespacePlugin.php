<?php

namespace Netgen\Bundle\MoreBundle\Layouts\Block\Plugin;

use Exception;
use Netgen\BlockManager\Block\BlockDefinition\BlockDefinitionHandlerInterface;
use Netgen\BlockManager\Block\BlockDefinition\Handler\Plugin;
use Netgen\BlockManager\Parameters\ParameterBuilderInterface;
use Netgen\BlockManager\Parameters\ParameterType;

class VerticalWhitespacePlugin extends Plugin
{

    /**
     * Returns the fully qualified class name of the handler which this
     * plugin extends.
     *
     * @return string|string[]
     */
    public static function getExtendedHandler()
    {
        return BlockDefinitionHandlerInterface::class;
    }

    /**
     * Builds the parameters by using provided parameter builder.
     *
     * @param \Netgen\BlockManager\Parameters\ParameterBuilderInterface $builder
     */
    public function buildParameters(ParameterBuilderInterface $builder)
    {
        $designGroup = array(self::GROUP_DESIGN);

        $builder->add('vertical_whitespace:enabled', ParameterType\Compound\BooleanType::class,
                array(
                    'default_value' => false,
                    'label' => 'block.plugin.vertical_whitespace.enabled',
                    'groups' => $designGroup,
            )
        );

        $builder->get('vertical_whitespace:enabled')->add('vertical_whitespace:position', ParameterType\ChoiceType::class,
            array(
                'default_value' => 'both',
                'label' => 'block.plugin.vertical_whitespace.position',
                'options' =>
                    [
                        'block.plugin.vertical_whitespace.top_and_bottom' => 'both',
                        'block.plugin.vertical_whitespace.only_top' => 'top',
                        'block.plugin.vertical_whitespace.only_bottom' => 'bottom'
                    ],
                'groups' => $designGroup,
            )
        );

        $builder->get('vertical_whitespace:enabled')->add('vertical_whitespace:size', ParameterType\ChoiceType::class,
            array(
                'default_value' => 'md',
                'label' => 'block.plugin.vertical_whitespace.size',
                'options' =>
                    [
                        'block.plugin.vertical_whitespace.large' => 'lg',
                        'block.plugin.vertical_whitespace.medium' => 'md',
                        'block.plugin.vertical_whitespace.small' => 'sm'
                    ],
                'groups' => $designGroup,
            )
        );

    }
}
