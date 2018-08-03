<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Layouts\Block\Plugin;

use Netgen\BlockManager\Standard\Block\BlockDefinition\Handler\TitleHandler;
use Netgen\BlockManager\Block\BlockDefinition\Handler\Plugin;
use Netgen\BlockManager\Parameters\ParameterBuilderInterface;
use Netgen\BlockManager\Parameters\ParameterType;

class TitleIconPlugin extends Plugin
{
    public static function getExtendedHandlers(): array
    {
        return [TitleHandler::class];
    }

    public static function getExtendedHandler(): array
    {
        return self::getExtendedHandlers();
    }

    public function buildParameters(ParameterBuilderInterface $builder): void
    {
        $designGroup = [self::GROUP_DESIGN];

        $builder->add(
            'title_icon:enabled',
            ParameterType\Compound\BooleanType::class,
            [
                'default_value' => false,
                'label' => 'block.plugin.title_icon.enabled',
                'groups' => $designGroup,
            ]
        );

        $builder->get('title_icon:enabled')->add(
            'title_icon:css_class',
            ParameterType\TextLineType::class,
            [
                'default_value' => '',
                'label' => 'block.plugin.title_icon.css_class',
                'groups' => $designGroup,
            ]
        );
    }
}
