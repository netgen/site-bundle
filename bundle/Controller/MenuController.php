<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use FOS\HttpCache\ResponseTagger;
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
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var \FOS\HttpCache\ResponseTagger
     */
    protected $responseTagger;

    public function __construct(
        MenuProviderInterface $menuProvider,
        RendererProviderInterface $menuRenderer,
        ConfigResolverInterface $configResolver,
        ResponseTagger $responseTagger
    ) {
        $this->menuProvider = $menuProvider;
        $this->menuRenderer = $menuRenderer;
        $this->configResolver = $configResolver;
        $this->responseTagger = $responseTagger;
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
            'template' => $this->configResolver->getParameter('template.menu', 'ngsite'),
        ];

        if ($request->attributes->has('template')) {
            $menuOptions['template'] = $request->attributes->get('template');
        }

        $response = new Response();

        $menuLocationId = $menu->getAttribute('location-id');
        if (!empty($menuLocationId)) {
            $this->responseTagger->addTags(['location-' . $menuLocationId]);
        }

        $this->processCacheSettings($request, $response);

        $response->setContent($this->menuRenderer->get()->render($menu, $menuOptions));

        return $response;
    }
}
