<?php

namespace Netgen\Bundle\MoreBundle\Templating;

use Netgen\Bundle\MoreBundle\Helper\SiteInfoHelper;
use Netgen\Bundle\MoreBundle\Helper\LayoutHelper;
use Symfony\Component\HttpFoundation\RequestStack;

class GlobalVariable
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
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    protected $requestStack;

    /**
     * @var \eZ\Publish\API\Repository\Values\Content\Content
     */
    protected $layout;

    /**
     * Constructor.
     *
     * @param \Netgen\Bundle\MoreBundle\Helper\SiteInfoHelper $siteInfoHelper
     * @param \Netgen\Bundle\MoreBundle\Helper\LayoutHelper $layoutHelper
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     */
    public function __construct(SiteInfoHelper $siteInfoHelper, LayoutHelper $layoutHelper, RequestStack $requestStack)
    {
        $this->siteInfoHelper = $siteInfoHelper;
        $this->layoutHelper = $layoutHelper;
        $this->requestStack = $requestStack;
    }

    /**
     * Returns the SiteInfo location.
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Location
     */
    public function getSiteInfoLocation()
    {
        return $this->siteInfoHelper->getSiteInfoLocation();
    }

    /**
     * Returns the SiteInfo content.
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Content
     */
    public function getSiteInfoContent()
    {
        return $this->siteInfoHelper->getSiteInfoContent();
    }

    /**
     * Returns the current layout.
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Content
     */
    public function getLayout()
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request !== null && $this->layout === null) {
            $locationId = $request->attributes->get('locationId');
            $pathInfo = $request->attributes->get('semanticPathinfo') . $request->attributes->get('viewParametersString');

            $this->layout = $this->layoutHelper->getLayout($locationId, $pathInfo);
        }

        return $this->layout;
    }
}
