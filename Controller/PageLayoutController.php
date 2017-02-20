<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use Knp\Menu\Provider\MenuProviderInterface;
use Knp\Menu\Renderer\RendererProviderInterface;
use Netgen\Bundle\EzPlatformSiteApiBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Knp\Menu\ItemInterface;

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
     * Constructor.
     *
     * @param \Knp\Menu\Provider\MenuProviderInterface $menuProvider
     * @param \Knp\Menu\Renderer\RendererProviderInterface $menuRenderer
     */
    public function __construct(MenuProviderInterface $menuProvider, RendererProviderInterface $menuRenderer)
    {
        $this->menuProvider = $menuProvider;
        $this->menuRenderer = $menuRenderer;
    }

    /**
     * Returns rendered menu.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $menuName
     * @param mixed $activeItemId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function menu(Request $request, $menuName, $activeItemId)
    {
        $requestAttributes = $request->attributes;

        $menu = $this->menuProvider->get($menuName);
        $menu->setChildrenAttribute(
            'class',
            $requestAttributes->get('ulClass') ?: 'nav navbar-nav'
        );

        if (!empty($menu[$activeItemId]) && $menu[$activeItemId] instanceof ItemInterface) {
            $menu[$activeItemId]->setCurrent(true);
        }

        $menuOptions = array(
            'firstClass' => $requestAttributes->get('firstClass') ?: 'firstli',
            'currentClass' => $requestAttributes->get('currentClass') ?: 'active',
            'lastClass' => $requestAttributes->get('lastClass') ?: 'lastli',
        );

        if ($requestAttributes->has('template')) {
            $menuOptions['template'] = $requestAttributes->get('template');
        }

        $menuContent = $this->menuRenderer->get()->render(
            $menu,
            $menuOptions
        );

        $response = new Response();

        $menuLocationId = $menu->getAttribute('location-id');
        if (!empty($menuLocationId)) {
            $response->headers->set('X-Location-Id', $menuLocationId);
        }

        $this->processCacheSettings($request, $response);

        $response->setContent($menuContent);

        return $response;
    }

    /**
     * Returns rendered region template.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param mixed $layoutId
     * @param string $region
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function region(Request $request, $layoutId, $region)
    {
        $requestAttributes = $request->attributes;

        $response = new Response();
        $layout = $this->getSite()->getLoadService()->loadContent($layoutId);

        /** @var $pageValue \eZ\Publish\Core\FieldType\Page\Value */
        $pageValue = $layout->getField('page')->value;

        $template = 'NetgenMoreBundle:parts:layout_region.html.twig';
        if ($requestAttributes->has('template')) {
            $template = $requestAttributes->get('template');
        }

        $this->processCacheSettings($request, $response);
        $response->headers->set('X-Location-Id', $layout->contentInfo->mainLocationId);

        foreach ($pageValue->page->zones as $zone) {
            if (strtolower($zone->identifier) == strtolower($region) && !empty($zone->blocks)) {
                return $this->render(
                    $template,
                    array(
                        'zone' => $zone,
                        'region' => $region,
                        'css_class' => $requestAttributes->get('cssClass') ?: false,
                        'params' => $requestAttributes->get('params') ?: array(),
                        'block_specific_params' => $requestAttributes->get('blockSpecificParams') ?: array(),
                    ),
                    $response
                );
            }
        }

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

        if ($public) {
            $response->setPublic();
        }
    }
}
