<?php

namespace Netgen\Bundle\MoreBundle\Imagine;

use eZ\Bundle\EzPublishCoreBundle\Imagine\IORepositoryResolver as BaseIORepositoryResolver;
use eZ\Publish\Core\IO\IOServiceInterface;
use Symfony\Component\Routing\RequestContext;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;

class IORepositoryResolver extends BaseIORepositoryResolver
{
    /**
     * @var \Symfony\Component\Routing\RequestContext
     */
    protected $requestContext;

    /**
     * @param \eZ\Publish\Core\IO\IOServiceInterface $ioService
     * @param \Symfony\Component\Routing\RequestContext $requestContext
     * @param \Liip\ImagineBundle\Imagine\Filter\FilterConfiguration $filterConfiguration
     */
    public function __construct( IOServiceInterface $ioService, RequestContext $requestContext, FilterConfiguration $filterConfiguration )
    {
        parent::__construct( $ioService, $requestContext, $filterConfiguration );

        $this->requestContext = $requestContext;
    }

    /**
     * Returns base URL for current request context.
     *
     * @return string
     */
    protected function getBaseUrl()
    {
        $baseUrl = $this->requestContext->getBaseUrl();
        if ( substr( $this->requestContext->getBaseUrl(), -4 ) === '.php' )
        {
            $baseUrl = pathinfo( $this->requestContext->getBaseUrl(), PATHINFO_DIRNAME );
        }

        return rtrim( $baseUrl, '/\\' );
    }
}
