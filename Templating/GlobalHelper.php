<?php

namespace Netgen\Bundle\MoreBundle\Templating;

use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\Core\MVC\Legacy\Templating\GlobalHelper as BaseGlobalHelper;
use eZ\Publish\API\Repository\ContentService;
use Netgen\Bundle\MoreBundle\Helper\LayoutHelper;
use NgMoreFunctionCollection;
use eZContentObjectTreeNode;
use eZContentObject;
use Closure;

class GlobalHelper extends BaseGlobalHelper
{
    /**
     * @var \Closure
     */
    protected $legacyKernel;

    /**
     * @var \eZ\Publish\API\Repository\ContentService
     */
    protected $contentService;

    /**
     * @var \Netgen\Bundle\MoreBundle\Helper\LayoutHelper
     */
    protected $layoutHelper;

    /**
     * @var \eZ\Publish\API\Repository\Values\Content\Location
     */
    protected $siteInfoLocation;

    /**
     * @var \eZ\Publish\API\Repository\Values\Content\Content
     */
    protected $layout;

    /**
     * Sets the legacy kernel
     *
     * @param \Closure $legacyKernel
     */
    public function setLegacyKernel( Closure $legacyKernel )
    {
        $this->legacyKernel = $legacyKernel;
    }

    /**
     * Sets the content service
     *
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     */
    public function setContentService( ContentService $contentService )
    {
        $this->contentService = $contentService;
    }

    /**
     * Sets the layout helper
     *
     * @param \Netgen\Bundle\MoreBundle\Helper\LayoutHelper $layoutHelper
     */
    public function setLayoutHelper( LayoutHelper $layoutHelper )
    {
        $this->layoutHelper = $layoutHelper;
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
        if ( $this->layout === null )
        {
            $locationId = $this->request->attributes->get( 'locationId' );
            $pathInfo = $this->request->attributes->get( 'semanticPathinfo' ) . $this->request->attributes->get( 'viewParametersString' );

            $layout = $this->layoutHelper->getLayout( $locationId, $pathInfo );
            if ( $layout instanceof Content )
            {
                $this->layout = $layout;
            }
            else
            {
                $this->layout = false;
            }
        }

        return $this->layout;
    }
}
