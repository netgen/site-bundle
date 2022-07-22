<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Layouts\Block\Plugin;

use Netgen\Layouts\Block\BlockDefinition\BlockDefinitionHandlerInterface;
use Netgen\Layouts\Block\BlockDefinition\Handler\Plugin;
use Netgen\Layouts\Parameters\ParameterBuilderInterface;
use Netgen\Layouts\Parameters\ParameterType;

use function array_flip;

class VerticalWhitespacePlugin extends Plugin
{
    /**
     * The list of positions available. Keys should be identifiers, while values
     * should be human readable names of the positions.
     */
    private array $positions;

    /**
     * The list of sizes available. Keys should be identifiers, while values
     * should be human readable names of the sizes.
     */
    private array $sizes;

    public function __construct(array $positions, array $sizes)
    {
        $this->positions = $positions;
        $this->sizes = $sizes;
    }

    public static function getExtendedHandlers(): array
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
            ],
        );

        $builder->get('vertical_whitespace:enabled')->add(
            'vertical_whitespace:position',
            ParameterType\ChoiceType::class,
            [
                'default_value' => 'both',
                'label' => 'block.plugin.vertical_whitespace.position',
                'options' => array_flip($this->positions),
                'groups' => $designGroup,
            ],
        );

        $builder->get('vertical_whitespace:enabled')->add(
            'vertical_whitespace:size',
            ParameterType\ChoiceType::class,
            [
                'default_value' => 'md',
                'label' => 'block.plugin.vertical_whitespace.size',
                'options' => array_flip($this->sizes),
                'groups' => $designGroup,
            ],
        );
    }
}
