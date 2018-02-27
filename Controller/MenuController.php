<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Controller;

use EzSystems\PlatformHttpCacheBundle\Handler\TagHandlerInterface;
use Knp\Menu\ItemInterface;
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
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $menuName
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderMenu(Request $request, $menuName)
    {
        $menu = $this->menuProvider->get($menuName);

        $menu->setChildrenAttribute('class', $request->attributes->get('ulClass') ?: 'nav navbar-nav');

        $activeItemId = $request->attributes->get('activeItemId');
        if (!empty($menu[$activeItemId]) && $menu[$activeItemId] instanceof ItemInterface) {
            $menu[$activeItemId]->setCurrent(true);
        }

        $menuOptions = array(
            'firstClass' => $request->attributes->get('firstClass') ?: 'firstli',
            'currentClass' => $request->attributes->get('currentClass') ?: 'active',
            'lastClass' => $request->attributes->get('lastClass') ?: 'lastli',
        );

        $menuOptions['template'] = $this->getConfigResolver()->getParameter('template.menu', 'ngmore');
        if ($request->attributes->has('template')) {
            $menuOptions['template'] = $request->attributes->get('template');
        }

        $menuContent = $this->menuRenderer->get()->render($menu, $menuOptions);

        $response = new Response();

        $menuLocationId = $menu->getAttribute('location-id');
        if (!empty($menuLocationId)) {
            $this->tagHandler->addTagHeaders($response, array('location-' . $menuLocationId));
        }

        $this->processCacheSettings($request, $response);

        $response->setContent($menuContent);

        return $response;
    }
}
