<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use Symfony\Component\HttpFoundation\Response;
use eZContentObject;

class PageLayoutController extends Controller
{
    /**
     * Returns rendered relation menu template
     *
     * @param mixed $activeLocationId
     * @param string $template
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function relationMenu( $activeLocationId, $template = null )
    {
        $response = new Response();
        $response->setPublic()->setSharedMaxAge( 86400 );

        $siteInfoContent = $this->get( 'netgen_more.helper.site_info_helper' )->getSiteInfoContent();
        $relationList = $this->getLegacyKernel()->runCallback(
            function() use ( $siteInfoContent )
            {
                $object = eZContentObject::fetch( $siteInfoContent->id );
                $attributes = array_values( $object->fetchAttributesByIdentifier( array( 'main_menu' ) ) );

                $attributeContent = $attributes[0]->content();
                return $attributeContent['relation_list'];
            }
        );

        return $this->render(
            $template !== null ? $template : 'NetgenMoreBundle:menu:relation_menu.html.twig',
            array(
                'relationList' => $relationList,
                'activeLocationId' => $activeLocationId
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
