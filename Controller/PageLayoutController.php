<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use Knp\Menu\Provider\MenuProviderInterface;
use Knp\Menu\Renderer\RendererProviderInterface;
use Netgen\Bundle\EzPlatformSiteApiBundle\Controller\Controller;
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
     * @param string $menuName
     * @param mixed $activeItemId
     * @param string $ulClass
     * @param string $firstClass
     * @param string $currentClass
     * @param string $lastClass
     * @param string $template
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function menu($menuName, $activeItemId, $ulClass = 'nav navbar-nav', $firstClass = 'firstli', $currentClass = 'active', $lastClass = 'lastli', $template = null)
    {
        $menu = $this->menuProvider->get($menuName);
        $menu->setChildrenAttribute('class', $ulClass);

        if (!empty($menu[$activeItemId]) && $menu[$activeItemId] instanceof ItemInterface) {
            $menu[$activeItemId]->setCurrent(true);
        }

        $menuOptions = array(
            'firstClass' => $firstClass,
            'currentClass' => $currentClass,
            'lastClass' => $lastClass,
        );

        if ($template !== null) {
            $menuOptions['template'] = $template;
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

        $response->setContent($menuContent);

        return $response;
    }

    /**
     * Returns rendered region template.
     *
     * @param mixed $layoutId
     * @param string $region
     * @param string|bool $cssClass
     * @param array $params
     * @param array $blockSpecificParams
     * @param string $template
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function region($layoutId, $region, $cssClass = false, $params = array(), $blockSpecificParams = array(), $template = null)
    {
        $response = new Response();
        $layout = $this->getSite()->getLoadService()->loadContent($layoutId);

        /** @var $pageValue \eZ\Publish\Core\FieldType\Page\Value */
        $pageValue = $layout->getField('page')->value;

        foreach ($pageValue->page->zones as $zone) {
            if (strtolower($zone->identifier) == strtolower($region) && !empty($zone->blocks)) {
                return $this->render(
                    $template !== null ? $template : 'NetgenMoreBundle:parts:layout_region.html.twig',
                    array(
                        'zone' => $zone,
                        'region' => $region,
                        'css_class' => $cssClass,
                        'params' => $params,
                        'block_specific_params' => $blockSpecificParams,
                    ),
                    $response
                );
            }
        }

        $response->headers->set('X-Location-Id', $layout->contentInfo->mainLocationId);

        return $response;
    }
}
