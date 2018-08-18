<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Layouts\Block\Plugin;

use Netgen\BlockManager\Block\BlockDefinition\ContainerDefinitionHandlerInterface;
use Netgen\BlockManager\Block\BlockDefinition\Handler\Plugin;
use Netgen\BlockManager\Parameters\ParameterBuilderInterface;
use Netgen\BlockManager\Parameters\ParameterType;
use Netgen\BlockManager\Standard\Block\BlockDefinition\Handler\ListHandler;

class BackgroundColorPlugin extends Plugin
{
    /**
     * The list of colors available. Keys should be identifiers, while values
     * should be human readable names of the colors.
     *
     * @var array
     */
    private $colors = [];

    public function __construct(array $colors)
    {
        $this->colors = $colors;
    }

    public static function getExtendedHandlers(): array
    {
        return [ListHandler::class, ContainerDefinitionHandlerInterface::class];
    }

    public function buildParameters(ParameterBuilderInterface $builder): void
    {
        $designGroup = [self::GROUP_DESIGN];

        $builder->add(
            'background_color:enabled',
            ParameterType\Compound\BooleanType::class,
            [
                'default_value' => false,
                'label' => 'block.plugin.background_color.enabled',
                'groups' => $designGroup,
            ]
        );

        $builder->get('background_color:enabled')->add(
            'background_color:color',
            ParameterType\ChoiceType::class,
            [
                'label' => 'block.plugin.background_color.color',
                'options' => array_flip($this->colors),
                'groups' => $designGroup,
            ]
        );
    }
}
