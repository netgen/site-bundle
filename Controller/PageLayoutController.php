<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use Symfony\Component\HttpFoundation\Response;
use Knp\Menu\ItemInterface;

class PageLayoutController extends Controller
{
    /**
     * Returns rendered menu
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
    public function menu( $menuName, $activeItemId, $ulClass = 'nav navbar-nav', $firstClass = 'firstli', $currentClass = 'active', $lastClass = 'lastli', $template = null )
    {
        /** @var \Knp\Menu\ItemInterface $menu */
        $menu = $this->container->get( 'knp_menu.menu_provider' )->get( $menuName );
        $menu->setChildrenAttribute( 'class', $ulClass );

        if ( !empty( $menu[$activeItemId] ) && $menu[$activeItemId] instanceof ItemInterface )
        {
            $menu[$activeItemId]->setCurrent( true );
        }

        $menuOptions = array(
            'firstClass' => $firstClass,
            'currentClass' => $currentClass,
            'lastClass' => $lastClass,
        );

        if ( $template !== null )
        {
            $menuOptions['template'] = $template;
        }

        /** @var \Knp\Menu\Renderer\RendererInterface $menuRenderer */
        $menuRenderer = $this->container->get( 'knp_menu.renderer_provider' )->get();
        $menuContent = $menuRenderer->render(
            $menu,
            $menuOptions
        );

        $response = new Response();

        $response->setPublic()->setSharedMaxAge( 86400 );
        $response->setContent( $menuContent );

        return $response;
    }

    /**
     * Returns rendered region template
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
    public function region( $layoutId, $region, $cssClass = false, $params = array(), $blockSpecificParams = array(), $template = null )
    {
        $response = new Response();
        $response->setPublic()->setSharedMaxAge( 300 );

        $layout = $this->getRepository()->getContentService()->loadContent( $layoutId );

        /** @var $pageValue \eZ\Publish\Core\FieldType\Page\Value */
        $pageValue = $this->get( 'ezpublish.translation_helper' )->getTranslatedField( $layout, 'page' )->value;

        foreach ( $pageValue->page->zones as $zone )
        {
            if ( strtolower( $zone->identifier ) == strtolower( $region ) && !empty( $zone->blocks ) )
            {
                return $this->render(
                    $template !== null ? $template : 'NetgenMoreBundle:parts:layout_region.html.twig',
                    array(
                        'zone' => $zone,
                        'region' => $region,
                        'css_class' => $cssClass,
                        'params' => $params,
                        'blockSpecificParams' => $blockSpecificParams
                    ),
                    $response
                );
            }
        }

        return $response;
    }
}
