<?php

namespace Netgen\Bundle\MoreBundle\Templating;

use Netgen\Bundle\MoreBundle\Helper\SiteInfoHelper;
use Netgen\Bundle\MoreBundle\Helper\LayoutHelper;
use Symfony\Component\HttpFoundation\Request;

class GlobalHelper
{
    /**
     * @var \Netgen\Bundle\MoreBundle\Helper\SiteInfoHelper
     */
    protected $siteInfoHelper;

    /**
     * @var \Netgen\Bundle\MoreBundle\Helper\LayoutHelper
     */
    protected $layoutHelper;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @var \eZ\Publish\API\Repository\Values\Content\Content
     */
    protected $layout;

    /**
     * Constructor
     *
     * @param \Netgen\Bundle\MoreBundle\Helper\SiteInfoHelper $siteInfoHelper
     * @param \Netgen\Bundle\MoreBundle\Helper\LayoutHelper $layoutHelper
     */
    public function __construct( SiteInfoHelper $siteInfoHelper, LayoutHelper $layoutHelper )
    {
        $this->siteInfoHelper = $siteInfoHelper;
        $this->layoutHelper = $layoutHelper;
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
        return $this->siteInfoHelper->getSiteInfoLocation();
    }

    /**
     * Returns the SiteInfo content
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Content
     */
    public function getSiteInfoContent()
    {
        return $this->siteInfoHelper->getSiteInfoContent();
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
