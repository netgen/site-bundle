<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Publish\Core\MVC\Symfony\Controller\Controller;
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

        $pageLayout = $this->container->get( 'netgen_more.component.page_layout' );

        return $this->render(
            'NetgenMoreBundle::page_header.html.twig',
            array(
                'mainCategoryLocationId' => $mainCategoryLocationId,
                'rootLocation' => $pageLayout->getRootLocation(),
                'siteInfoLocation' => $pageLayout->getSiteInfoLocation()
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

        $pageLayout = $this->container->get( 'netgen_more.component.page_layout' );

        return $this->render(
            'NetgenMoreBundle::page_footer.html.twig',
            array(
                'mainCategoryLocationId' => $mainCategoryLocationId,
                'rootLocation' => $pageLayout->getRootLocation(),
                'siteInfoLocation' => $pageLayout->getSiteInfoLocation()
            ),
            $response
        );
    }

    /**
     * Returns rendered region template
     *
     * @param int $layoutContentId
     * @param string $region
     * @param bool $cssClass
     * @param array $params
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function region( $layoutContentId, $region, $cssClass = false, $params = array() )
    {
        $response = new Response();
        $response->setPublic()->setSharedMaxAge( 300 );

        $repository = $this->getRepository();
        $configResolver = $this->getConfigResolver();

        $layoutContent = $repository->getContentService()->loadContent( $layoutContentId );

        /** @var $pageValue \eZ\Publish\Core\FieldType\Page\Value */
        $pageValue = $layoutContent->getFieldValue( 'page' );

        foreach ( $pageValue->page->zones as $zone )
        {
            if ( strcasecmp( $zone->identifier, $region ) == 0 )
            {
                if ( count( $zone->blocks ) > 0 )
                {
                    return $this->render(
                        'NetgenMoreBundle::layout_region.html.twig',
                        array(
                            'layoutRegionZone' => $zone,
                            'cssClass' => $cssClass,
                            'configResolver' => $configResolver,
                            'region' => $region,
                            'params' => $params
                        ),
                        $response
                    );
                }
            }
        }

        return $response;
    }
}
