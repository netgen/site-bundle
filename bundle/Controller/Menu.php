<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller;

use Ibexa\HttpCache\Handler\TagHandler;
use Knp\Menu\Provider\MenuProviderInterface;
use Knp\Menu\Renderer\RendererProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class Menu extends Controller
{
    public function __construct(
        private readonly MenuProviderInterface $menuProvider,
        private readonly RendererProviderInterface $menuRenderer,
        private readonly TagHandler $tagHandler,
    ) {
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
            'template' => $this->getConfigResolver()->getParameter('template.menu', 'ngsite'),
        ];

        if ($request->attributes->has('template')) {
            $menuOptions['template'] = $request->attributes->get('template');
        }

        $response = new Response();

        $menuLocationId = $menu->getAttribute('location-id');
        if ($menuLocationId !== null) {
            $this->tagHandler->addLocationTags([$menuLocationId]);
        }

        $this->processCacheSettings($request, $response);

        $response->setContent($this->menuRenderer->get()->render($menu, $menuOptions));

        return $response;
    }
}
