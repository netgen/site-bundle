<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Layouts\Block\Plugin;

use Netgen\Layouts\Block\BlockDefinition\Handler\Plugin;
use Netgen\Layouts\Parameters\ParameterBuilderInterface;
use Netgen\Layouts\Parameters\ParameterType;

use function array_flip;

class FeaturesComponentGridPlugin extends Plugin
{
    /**
     * The list of columns available. Key should be number of columns, while values
     * should be human readable names of the columns.
     *
     * @var string[]
     */
    private array $columns;

    /**
     * @param string[] $columns
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }

    public static function getExtendedIdentifiers(): iterable
    {
        yield 'ibexa_component_features';
    }

    public function buildParameters(ParameterBuilderInterface $builder): void
    {
        $builder->add(
            'number_of_columns',
            ParameterType\ChoiceType::class,
            [
                'required' => true,
                'default_value' => 3,
                'options' => array_flip($this->columns),
                'label' => 'block.plugin.features_component_grid.number_of_columns',
                'groups' => [self::GROUP_DESIGN],
            ],
        );
    }
}
