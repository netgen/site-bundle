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
                'mainCategoryLocationId' => $mainCategoryLocationId,
                'rootLocation' => $pageLayout->getRootLocation(),
                'siteInfoLocation' => $pageLayout->getSiteInfoLocation(),
                'configResolver' => $this->getConfigResolver()
            ),
            $response
        );
    }

    /**
     * Displays path for a given $locationId
     *
     * @param mixed $locationId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function path( $locationId )
    {
        $pathArray = array();

        $locationService = $this->getRepository()->getLocationService();
        $path = $locationService->loadLocation( $locationId )->path;

        // The root location can be defined at site access level
        $rootLocationId = $this->getConfigResolver()->getParameter( 'content.tree_root.location_id' );

        /** @var \eZ\Publish\Core\Helper\TranslationHelper $translationHelper */
        $translationHelper = $this->get( 'ezpublish.translation_helper' );

        $isRootLocation = false;

        // Shift of location "1" from path as it is not a fully valid location and not readable by most users
        array_shift( $path );

        for ( $i = 0; $i < count( $path ); $i++ )
        {
            $location = $locationService->loadLocation( $path[$i] );
            // if root location hasn't been found yet
            if ( !$isRootLocation )
            {
                // If we reach the root location, we begin to add item to the path array from it
                if ( $location->id == $rootLocationId )
                {
                    $isRootLocation = true;
                    $pathArray[] = array(
                        'text' => $translationHelper->getTranslatedContentNameByContentInfo( $location->contentInfo ),
                        'url' => $location->id != $locationId ? $this->generateUrl( $location ) : false
                    );
                }
            }
            // The root location has already been reached, so we can add items to the path array
            else
            {
                $pathArray[] = array(
                    'text' => $translationHelper->getTranslatedContentNameByContentInfo( $location->contentInfo ),
                    'url' => $location->id != $locationId ? $this->generateUrl( $location ) : false
                );
            }
        }

        // We don't want the path to be displayed if we are on the frontpage
        // which means we display it only if we have several items in it
        if ( count( $pathArray ) <= 1 )
        {
            return new Response();
        }

        return $this->render(
            'NetgenMoreBundle::page_path.html.twig',
            array(
                'pathArray' => $pathArray
            )
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
                'mainCategoryLocationId' => $mainCategoryLocationId,
                'rootLocation' => $pageLayout->getRootLocation(),
                'siteInfoLocation' => $pageLayout->getSiteInfoLocation(),
                'configResolver' => $this->getConfigResolver()
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
                        'params' => $params,
                        'configResolver' => $this->getConfigResolver()
                    ),
                    $response
                );
            }
        }

        return $response;
    }
}
