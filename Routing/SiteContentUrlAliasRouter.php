<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Routing;

use eZ\Publish\Core\MVC\Symfony\Routing\Generator\UrlAliasGenerator;
use LogicException;
use Netgen\EzPlatformSiteApi\API\Values\Content;
use Netgen\EzPlatformSiteApi\API\Values\ContentInfo;
use RuntimeException;
use Symfony\Cmf\Component\Routing\ChainedRouterInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\RouteCollection;

class SiteContentUrlAliasRouter implements ChainedRouterInterface, RequestMatcherInterface
{
    /**
     * @var \eZ\Publish\Core\MVC\Symfony\Routing\Generator\UrlAliasGenerator
     */
    protected $generator;

    /**
     * @var \Symfony\Component\Routing\RequestContext
     */
    protected $requestContext;

    public function __construct(UrlAliasGenerator $generator, RequestContext $requestContext)
    {
        $this->generator = $generator;
        $this->requestContext = $requestContext ?: new RequestContext();
    }

    public function matchRequest(Request $request)
    {
        throw new ResourceNotFoundException('ContentUrlAliasRouter does not support matching requests.');
    }

    /**
     * Generates a URL for Site API Content object or ContentInfo object, from the given parameters.
     *
     * @param mixed $name
     * @param mixed $parameters
     * @param mixed $referenceType
     */
    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        if (!$name instanceof Content && !$name instanceof ContentInfo) {
            throw new RouteNotFoundException('Could not match route');
        }

        if (empty($name->mainLocationId)) {
            throw new LogicException('Cannot generate an UrlAlias route for content without main location.');
        }

        return $this->generator->generate(
            $name->mainLocation->innerLocation,
            $parameters,
            $referenceType
        );
    }

    public function getRouteCollection()
    {
        return new RouteCollection();
    }

    public function setContext(RequestContext $context)
    {
        $this->requestContext = $context;
        $this->generator->setRequestContext($context);
    }

    public function getContext()
    {
        return $this->requestContext;
    }

    public function match($pathinfo)
    {
        throw new RuntimeException("The ContentUrlAliasRouter doesn't support the match() method.");
    }

    public function supports($name)
    {
        return $name instanceof Content || $name instanceof ContentInfo;
    }

    public function getRouteDebugMessage($name, array $parameters = array())
    {
        if ($name instanceof RouteObjectInterface) {
            return 'Route with key ' . $name->getRouteKey();
        }

        if ($name instanceof SymfonyRoute) {
            return 'Route with pattern ' . $name->getPath();
        }

        return $name;
    }
}
