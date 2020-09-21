<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use EzSystems\PlatformHttpCacheBundle\Handler\TagHandler;
use Knp\Menu\Provider\MenuProviderInterface;
use Knp\Menu\Renderer\RendererProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Menu extends Controller
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
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var \EzSystems\PlatformHttpCacheBundle\Handler\TagHandler
     */
    protected $tagHandler;

    public function __construct(
        MenuProviderInterface $menuProvider,
        RendererProviderInterface $menuRenderer,
        ConfigResolverInterface $configResolver,
        TagHandler $tagHandler
    ) {
        $this->menuProvider = $menuProvider;
        $this->menuRenderer = $menuRenderer;
        $this->configResolver = $configResolver;
        $this->tagHandler = $tagHandler;
    }

    /**
     * Renders the menu with provided name.
     */
    public function __invoke(Request $request, string $menuName): Response
    {
        $menu = $this->menuProvider->get($menuName);
        $menu->setChildrenAttribute('class', $request->attributes->get('ulClass') ?? 'nav navbar-nav');

        $menuOptions = [
            'firstClass' => $request->attributes->get('firstClass') ?? 'firstli',
            'currentClass' => $request->attributes->get('currentClass') ?? 'active',
            'lastClass' => $request->attributes->get('lastClass') ?? 'lastli',
            'template' => $this->configResolver->getParameter('template.menu', 'ngsite'),
        ];

        if ($request->attributes->has('template')) {
            $menuOptions['template'] = $request->attributes->get('template');
        }

        $response = new Response();

        $menuLocationId = $menu->getAttribute('location-id');
        if (!empty($menuLocationId)) {
            $this->tagHandler->addLocationTags([$menuLocationId]);
        }

        $this->processCacheSettings($request, $response);

        $response->setContent($this->menuRenderer->get()->render($menu, $menuOptions));

        return $response;
    }
}
