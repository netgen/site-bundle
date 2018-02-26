<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use EzSystems\PlatformHttpCacheBundle\Handler\TagHandlerInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\Provider\MenuProviderInterface;
use Knp\Menu\Renderer\RendererProviderInterface;
use Netgen\Bundle\EzPlatformSiteApiBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PageLayoutController extends Controller
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
     * Returns rendered menu.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $menuName
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function menu(Request $request, $menuName)
    {
        $menu = $this->menuProvider->get($menuName);

        $menu->setChildrenAttribute('class', $request->attributes->get('ulClass') ?: 'nav navbar-nav');

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

    /**
     * Configures the response with provided cache settings.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     */
    protected function processCacheSettings(Request $request, Response $response)
    {
        $cacheSettings = $request->attributes->get('cacheSettings');
        if (!is_array($cacheSettings)) {
            $cacheSettings = array('sharedMaxAge' => 86400);
        }

        $public = true;

        if (isset($cacheSettings['sharedMaxAge'])) {
            $response->setSharedMaxAge($cacheSettings['sharedMaxAge']);
            if (empty($cacheSettings['sharedMaxAge'])) {
                $public = false;
            }
        } elseif (isset($cacheSettings['maxAge'])) {
            $response->setMaxAge($cacheSettings['maxAge']);
            if (empty($cacheSettings['maxAge'])) {
                $public = false;
            }
        } else {
            $response->setSharedMaxAge(86400);
        }

        $public ? $response->setPublic() : $response->setPrivate();
    }
}
