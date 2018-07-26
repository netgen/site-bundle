<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Layouts\Block\Plugin;

use Netgen\BlockManager\Block\BlockDefinition\Handler\Plugin;
use Netgen\BlockManager\Parameters\ParameterBuilderInterface;
use Netgen\BlockManager\Parameters\ParameterType;
use Netgen\BlockManager\Block\BlockDefinition\ContainerDefinitionHandlerInterface;
use Netgen\BlockManager\Standard\Block\BlockDefinition\Handler\ListHandler;

class BackgroundColorPlugin extends Plugin
{
    public static function getExtendedHandler()
    {
        return [ListHandler::class, ContainerDefinitionHandlerInterface::class];
    }

    /**
     * @var array
     */
    private $colors = [];

    public function __construct(array $colors = [])
    {
        $this->colors = array_flip($colors);
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
                'options' => $this->colors,
                'groups' => $designGroup,
            ]
        );
    }
}
