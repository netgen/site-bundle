<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Layouts\Block\Plugin;

use Netgen\Layouts\Block\BlockDefinition\ContainerDefinitionHandlerInterface;
use Netgen\Layouts\Block\BlockDefinition\Handler\Plugin;
use Netgen\Layouts\Ibexa\Block\BlockDefinition\Handler\ComponentHandler;
use Netgen\Layouts\Parameters\ParameterBuilderInterface;
use Netgen\Layouts\Parameters\ParameterType;
use Netgen\Layouts\Standard\Block\BlockDefinition\Handler\ListHandler;

use function array_flip;

final class BackgroundColorPlugin extends Plugin
{
    /**
     * The list of colors available. Keys should be identifiers, while values
     * should be human readable names of the colors.
     *
     * @param array<string, string> $colors
     */
    public function __construct(private array $colors) {}

    public static function getExtendedHandlers(): iterable
    {
        yield ListHandler::class;

        yield ComponentHandler::class;

        yield ContainerDefinitionHandlerInterface::class;

        yield BackgroundColorPluginInterface::class;
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
            ],
        );

        $builder->get('background_color:enabled')->add(
            'background_color:color',
            ParameterType\ChoiceType::class,
            [
                'label' => 'block.plugin.background_color.color',
                'options' => array_flip($this->colors),
                'groups' => $designGroup,
            ],
        );
    }
}
