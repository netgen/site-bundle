<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Layouts\Block\Plugin;

use Netgen\Layouts\Block\BlockDefinition\BlockDefinitionHandlerInterface;
use Netgen\Layouts\Block\BlockDefinition\Handler\Plugin;
use Netgen\Layouts\Parameters\ParameterBuilderInterface;
use Netgen\Layouts\Parameters\ParameterType;
use function array_flip;

class VerticalMarginPlugin extends Plugin
{
    private array $top;

    private array $bottom;

    public function __construct(array $top, array $bottom)
    {
        $this->top = $top;
        $this->bottom = $bottom;
    }

    public static function getExtendedHandlers(): array
    {
        return [BlockDefinitionHandlerInterface::class];
    }

    public function buildParameters(ParameterBuilderInterface $builder): void
    {
        $designGroup = [self::GROUP_DESIGN];

        $builder->add(
            'vertical_margin:enabled',
            ParameterType\Compound\BooleanType::class,
            [
                'default_value' => false,
                'label' => 'block.plugin.vertical_margin.enabled',
                'groups' => $designGroup,
            ],
        );

        $builder->get('vertical_margin:enabled')->add(
            'vertical_margin:top',
            ParameterType\ChoiceType::class,
            [
                'default_value' => 'medium',
                'label' => 'block.plugin.vertical_margin.top',
                'options' => array_flip($this->top),
                'groups' => $designGroup,
            ],
        );

        $builder->get('vertical_margin:enabled')->add(
            'vertical_margin:bottom',
            ParameterType\ChoiceType::class,
            [
                'default_value' => 'medium',
                'label' => 'block.plugin.vertical_margin.bottom',
                'options' => array_flip($this->bottom),
                'groups' => $designGroup,
            ],
        );
    }
}
