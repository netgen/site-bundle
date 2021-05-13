<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Layouts\Block\Plugin;

use Netgen\Layouts\Block\BlockDefinition\Handler\Plugin;
use Netgen\Layouts\Parameters\ParameterBuilderInterface;
use Netgen\Layouts\Parameters\ParameterType;
use Netgen\Layouts\Standard\Block\BlockDefinition\Handler\TitleHandler;

class TitleIconPlugin extends Plugin
{
    public static function getExtendedHandlers(): array
    {
        return [TitleHandler::class];
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
            ],
        );

        $builder->get('title_icon:enabled')->add(
            'title_icon:css_class',
            ParameterType\TextLineType::class,
            [
                'default_value' => '',
                'label' => 'block.plugin.title_icon.css_class',
                'groups' => $designGroup,
            ],
        );
    }
}
