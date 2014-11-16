<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use Symfony\Component\HttpFoundation\Response;
use Knp\Menu\ItemInterface;

class PageLayoutController extends Controller
{
    /**
     * Returns rendered relation menu template
     *
     * @deprecated This method is deprecated in favor of PageLayoutController::mainMenu
     *             and will be removed in NetgenMoreBundle 2.1
     *
     * @param mixed $activeLocationId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function relationMenu( $activeLocationId )
    {
        return $this->menu( $activeLocationId, 'ngmore_main_menu' );
    }

    /**
     * Returns rendered menu
     *
     * @param mixed $activeLocationId
     * @param string $menuName
     * @param string $ulClass
     * @param string $firstClass
     * @param string $currentClass
     * @param string $lastClass
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function menu( $activeLocationId, $menuName, $ulClass = 'nav navbar-nav', $firstClass = 'firstli', $currentClass = 'active', $lastClass = 'lastli' )
    {
        /** @var \Knp\Menu\ItemInterface $mainMenu */
        $mainMenu = $this->container->get( 'knp_menu.menu_provider' )->get( $menuName );
        $mainMenu->setChildrenAttribute( 'class', $ulClass );

        if ( !empty( $mainMenu[$activeLocationId] ) && $mainMenu[$activeLocationId] instanceof ItemInterface )
        {
            $mainMenu[$activeLocationId]->setCurrent( true );
        }

        /** @var \Knp\Menu\Renderer\RendererInterface $mainMenuRenderer */
        $mainMenuRenderer = $this->container->get( 'knp_menu.renderer_provider' )->get();
        $menuContent = $mainMenuRenderer->render(
            $mainMenu,
            array(
                'firstClass' => $firstClass,
                'currentClass' => $currentClass,
                'lastClass' => $lastClass
            )
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
     * @param string $template
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function region( $layoutId, $region, $cssClass = false, $params = array(), $template = null )
    {
        $response = new Response();
        $response->setPublic()->setSharedMaxAge( 300 );

        $layout = $this->getRepository()->getContentService()->loadContent( $layoutId );

        /** @var $pageValue \eZ\Publish\Core\FieldType\Page\Value */
        $pageValue = $layout->getFieldValue( 'page' );

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
                        'params' => $params
                    ),
                    $response
                );
            }
        }

        return $response;
    }
}
