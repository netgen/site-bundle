<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Layouts\Block\Plugin;

use Netgen\Layouts\Block\BlockDefinition\BlockDefinitionHandlerInterface;
use Netgen\Layouts\Block\BlockDefinition\Handler\Plugin;
use Netgen\Layouts\Parameters\ParameterBuilderInterface;
use Netgen\Layouts\Parameters\ParameterType;

use function array_flip;

class SetContainerPlugin extends Plugin
{
    /**
     * The list of sizes available. Keys should be identifiers, while values
     * should be human readable names of the sizes.
     */
    private array $sizes;

    public function __construct(array $sizes)
    {
        $this->sizes = $sizes;
    }

    public static function getExtendedHandlers(): array
    {
        return [BlockDefinitionHandlerInterface::class];
    }

    public function buildParameters(ParameterBuilderInterface $builder): void
    {
        $designGroup = [self::GROUP_DESIGN];

        $builder->remove('set_container');

        $builder->add(
            'set_container',
            ParameterType\Compound\BooleanType::class,
            [
                'label' => 'block.plugin.common_params.set_container',
                'translatable' => false,
                'groups' => $designGroup,
            ],
        );

        $builder->get('set_container')->add(
            'set_container:size',
            ParameterType\ChoiceType::class,
            [
                'default_value' => '',
                'label' => 'block.plugin.set_container.size',
                'translatable' => false,
                'options' => array_flip($this->sizes),
                'groups' => $designGroup,
            ],
        );
    }
}
