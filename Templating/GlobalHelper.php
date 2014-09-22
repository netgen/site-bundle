<?php

namespace Netgen\Bundle\MoreBundle\Templating;

use eZ\Publish\API\Repository\LocationService;
use Netgen\Bundle\MoreBundle\Helper\LayoutHelper;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Component\HttpFoundation\Request;

class GlobalHelper
{
    /**
     * @var \eZ\Publish\API\Repository\LocationService
     */
    protected $locationService;

    /**
     * @var \Netgen\Bundle\MoreBundle\Helper\LayoutHelper
     */
    protected $layoutHelper;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @var \eZ\Publish\API\Repository\Values\Content\Location
     */
    protected $siteInfoLocation;

    /**
     * @var \eZ\Publish\API\Repository\Values\Content\Content
     */
    protected $layout;

    /**
     * Constructor
     *
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     * @param \Netgen\Bundle\MoreBundle\Helper\LayoutHelper $layoutHelper
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     */
    public function __construct(
        LocationService $locationService,
        LayoutHelper $layoutHelper,
        ConfigResolverInterface $configResolver
    )
    {
        $this->locationService = $locationService;
        $this->layoutHelper = $layoutHelper;
        $this->configResolver = $configResolver;
    }

    /**
     * Sets the request
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function setRequest( Request $request = null )
    {
        $this->request = $request;
    }

    /**
     * Returns the SiteInfo location
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Location
     */
    public function getSiteInfoLocation()
    {
        if ( $this->siteInfoLocation === null )
        {
            $this->siteInfoLocation = $this->locationService->loadLocation(
                $this->configResolver->getParameter( 'SpecialNodes.SiteInfoNode', 'ngmore' )
            );
        }

        return $this->siteInfoLocation;
    }

    /**
     * Returns the current layout
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Content
     */
    public function getLayout()
    {
        if ( $this->request !== null && $this->layout === null )
        {
            $locationId = $this->request->attributes->get( 'locationId' );
            $pathInfo = $this->request->attributes->get( 'semanticPathinfo' ) . $this->request->attributes->get( 'viewParametersString' );

            $this->layout = $this->layoutHelper->getLayout( $locationId, $pathInfo );
        }

        return $this->layout;
    }
}
