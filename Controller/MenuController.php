<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Controller;

use EzSystems\PlatformHttpCacheBundle\Handler\TagHandlerInterface;
use Knp\Menu\Provider\MenuProviderInterface;
use Knp\Menu\Renderer\RendererProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MenuController extends Controller
{
    /**
     * @var \Knp\Menu\Provider\MenuProviderInterface
     */
    protected $menuProvider;

    /**
     * @var \Knp\Menu\Renderer\RendererProviderInterface
     */
    protected $menuRenderer;

    /**
     * @var \EzSystems\PlatformHttpCacheBundle\Handler\TagHandlerInterface
     */
    protected $tagHandler;

    public function __construct(
        MenuProviderInterface $menuProvider,
        RendererProviderInterface $menuRenderer,
        TagHandlerInterface $tagHandler
    ) {
        $this->menuProvider = $menuProvider;
        $this->menuRenderer = $menuRenderer;
        $this->tagHandler = $tagHandler;
    }

    /**
     * Renders the menu with provided name.
     */
    public function renderMenu(Request $request, string $menuName): Response
    {
        $menu = $this->menuProvider->get($menuName);
        $menu->setChildrenAttribute('class', $request->attributes->get('ulClass') ?: 'nav navbar-nav');

        $menuOptions = [
            'firstClass' => $request->attributes->get('firstClass') ?: 'firstli',
            'currentClass' => $request->attributes->get('currentClass') ?: 'active',
            'lastClass' => $request->attributes->get('lastClass') ?: 'lastli',
            'template' => $this->getConfigResolver()->getParameter('template.menu', 'ngmore'),
        ];

        if ($request->attributes->has('template')) {
            $menuOptions['template'] = $request->attributes->get('template');
        }

        $response = new Response();

        $menuLocationId = $menu->getAttribute('location-id');
        if (!empty($menuLocationId)) {
            $this->tagHandler->addTagHeaders($response, ['location-' . $menuLocationId]);
        }

        $this->processCacheSettings($request, $response);

        $response->setContent($this->menuRenderer->get()->render($menu, $menuOptions));

        return $response;
    }
}
