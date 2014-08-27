<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use Symfony\Component\HttpFoundation\Response;

class PageLayoutController extends Controller
{
    /**
     * Returns rendered header template
     *
     * @param int $mainCategoryLocationId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function header( $mainCategoryLocationId )
    {
        $response = new Response();
        $response->setPublic()->setSharedMaxAge( 86400 );

        $pageLayout = $this->get( 'netgen_more.component.page_layout' );

        return $this->render(
            'NetgenMoreBundle::page_header.html.twig',
            array(
                'mainCategoryLocationId' => $mainCategoryLocationId
            ),
            $response
        );
    }

    /**
     * Returns rendered footer template
     *
     * @param int $mainCategoryLocationId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function footer( $mainCategoryLocationId )
    {
        $response = new Response();
        $response->setPublic()->setSharedMaxAge( 86400 );

        $pageLayout = $this->get( 'netgen_more.component.page_layout' );

        return $this->render(
            'NetgenMoreBundle::page_footer.html.twig',
            array(
                'mainCategoryLocationId' => $mainCategoryLocationId
            ),
            $response
        );
    }

    /**
     * Returns rendered region template
     *
     * @param mixed $layoutId
     * @param string $region
     * @param string|bool $cssClass
     * @param array $params
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function region( $layoutId, $region, $cssClass = false, $params = array() )
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
                    'NetgenMoreBundle:parts:layout_region.html.twig',
                    array(
                        'zone' => $zone,
                        'region' => $region,
                        'cssClass' => $cssClass,
                        'params' => $params
                    ),
                    $response
                );
            }
        }

        return $response;
    }
}
