<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Layouts\Block\Plugin;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Netgen\IbexaSiteApi\API\Exceptions\TranslationNotMatchedException;
use Netgen\IbexaSiteApi\API\LoadService;
use Netgen\Layouts\API\Values\Block\Block;
use Netgen\Layouts\Block\BlockDefinition\ContainerDefinitionHandlerInterface;
use Netgen\Layouts\Block\BlockDefinition\Handler\Plugin;
use Netgen\Layouts\Block\DynamicParameters;
use Netgen\Layouts\Ibexa\Block\BlockDefinition\Handler\ComponentHandler;
use Netgen\Layouts\Ibexa\Parameters\ParameterType as IbexaParameterType;
use Netgen\Layouts\Parameters\ParameterBuilderInterface;
use Netgen\Layouts\Parameters\ParameterType;
use Netgen\Layouts\Standard\Block\BlockDefinition\Handler\ListHandler;

class BackgroundImagePlugin extends Plugin
{
    private LoadService $loadService;

    public function __construct(LoadService $loadService)
    {
        $this->loadService = $loadService;
    }

    public static function getExtendedHandlers(): array
    {
        return [
            ListHandler::class,
            ComponentHandler::class,
            ContainerDefinitionHandlerInterface::class,
            BackgroundImagePluginInterface::class,
        ];
    }

    public function buildParameters(ParameterBuilderInterface $builder): void
    {
        $designGroup = [self::GROUP_DESIGN];

        $builder->add(
            'background_image:enabled',
            ParameterType\Compound\BooleanType::class,
            [
                'default_value' => false,
                'label' => 'block.plugin.background_image.enabled',
                'groups' => $designGroup,
            ],
        );

        $builder->get('background_image:enabled')->add(
            'background_image:image',
            IbexaParameterType\ContentType::class,
            [
                'allow_invalid' => true,
                'label' => 'block.plugin.background_image.image',
                'groups' => $designGroup,
            ],
        );
    }

    public function getDynamicParameters(DynamicParameters $params, Block $block): void
    {
        $params['background_image:image_content'] = null;

        if ($block->getParameter('background_image:image')->isEmpty()) {
            return;
        }

        try {
            $params['background_image:image_content'] = $this->loadService->loadContent(
                $block->getParameter('background_image:image')->getValue(),
            );
        } catch (UnauthorizedException|NotFoundException|TranslationNotMatchedException $e) {
            // Do nothing
        }
    }
}
