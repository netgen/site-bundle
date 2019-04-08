<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Layouts\Block\Plugin;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use Netgen\BlockManager\API\Values\Block\Block;
use Netgen\BlockManager\Block\BlockDefinition\ContainerDefinitionHandlerInterface;
use Netgen\BlockManager\Block\BlockDefinition\Handler\Plugin;
use Netgen\BlockManager\Block\DynamicParameters;
use Netgen\BlockManager\Ez\Parameters\ParameterType as EzParameterType;
use Netgen\BlockManager\Parameters\ParameterBuilderInterface;
use Netgen\BlockManager\Parameters\ParameterType;
use Netgen\BlockManager\Standard\Block\BlockDefinition\Handler\ListHandler;
use Netgen\EzPlatformSiteApi\API\Exceptions\TranslationNotMatchedException;
use Netgen\EzPlatformSiteApi\API\LoadService;

class BackgroundImagePlugin extends Plugin
{
    /**
     * @var \Netgen\EzPlatformSiteApi\API\LoadService
     */
    private $loadService;

    public function __construct(LoadService $loadService)
    {
        $this->loadService = $loadService;
    }

    public static function getExtendedHandlers(): array
    {
        return [ListHandler::class, ContainerDefinitionHandlerInterface::class, BackgroundImagePluginInterface::class];
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
            ]
        );

        $builder->get('background_image:enabled')->add(
            'background_image:image',
            EzParameterType\ContentType::class,
            [
                'allow_invalid' => true,
                'label' => 'block.plugin.background_image.image',
                'groups' => $designGroup,
            ]
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
                $block->getParameter('background_image:image')->getValue()
            );
        } catch (UnauthorizedException | NotFoundException | TranslationNotMatchedException $e) {
            // Do nothing
        }
    }
}
