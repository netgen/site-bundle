<?php

namespace Netgen\Bundle\MoreBundle\Routing;

use Netgen\EzPlatformSiteApi\API\Values\Content;
use Netgen\EzPlatformSiteApi\API\Values\ContentInfo;
use eZ\Publish\Core\MVC\Symfony\Routing\Generator\UrlAliasGenerator;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Symfony\Cmf\Component\Routing\ChainedRouterInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use LogicException;
use RuntimeException;

class SiteContentUrlAliasRouter implements ChainedRouterInterface, RequestMatcherInterface
{
    /**
     * @var \Netgen\EzPlatformSiteApi\API\LoadService
     */
    protected $loadService;

    /**
     * @var \eZ\Publish\Core\MVC\Symfony\Routing\Generator\UrlAliasGenerator
     */
    protected $generator;

    /**
     * @var \Symfony\Component\Routing\RequestContext
     */
    protected $requestContext;

    /**
     * Constructor.
     *
     * @param \Netgen\EzPlatformSiteApi\API\LoadService $loadService
     * @param \eZ\Publish\Core\MVC\Symfony\Routing\Generator\UrlAliasGenerator $generator
     * @param \Symfony\Component\Routing\RequestContext $requestContext
     */
    public function __construct(
        LoadService $loadService,
        UrlAliasGenerator $generator,
        RequestContext $requestContext
    ) {
        $this->loadService = $loadService;
        $this->generator = $generator;
        $this->requestContext = $requestContext ?: new RequestContext();
    }

    /**
     * Tries to match a request with a set of routes.
     *
     * If the matcher can not find information, it must throw one of the exceptions documented
     * below.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request The request to match
     *
     * @return array An array of parameters
     *
     * @throws \Symfony\Component\Routing\Exception\ResourceNotFoundException If no matching resource could be found
     */
    public function matchRequest(Request $request)
    {
        throw new ResourceNotFoundException('ContentUrlAliasRouter does not support matching requests.');
    }

    /**
     * Generates a URL for Content object or ContentInfo object, from the given parameters.
     *
     * If the generator is not able to generate the URL, it must throw the RouteNotFoundException as documented below.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Content|\eZ\Publish\API\Repository\Values\Content\ContentInfo $name Content or ContentInfo instance
     * @param mixed $parameters An array of parameters
     * @param bool $absolute Whether to generate an absolute URL
     *
     * @throws \LogicException
     * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException
     * @throws \InvalidArgumentException
     *
     * @return string The generated URL
     */
    public function generate($name, $parameters = array(), $absolute = false)
    {
        if (!$name instanceof Content && !$name instanceof ContentInfo) {
            throw new RouteNotFoundException('Could not match route');
        }

        if (empty($name->mainLocationId)) {
            throw new LogicException('Cannot generate an UrlAlias route for content without main location.');
        }

        $mainLocation = $this->loadService->loadLocation($name->mainLocationId);

        return $this->generator->generate(
            $mainLocation->innerLocation,
            $parameters,
            $absolute
        );
    }

    /**
     * Gets the RouteCollection instance associated with this Router.
     *
     * @return \Symfony\Component\Routing\RouteCollection A RouteCollection instance
     */
    public function getRouteCollection()
    {
        return new RouteCollection();
    }

    /**
     * Sets the request context.
     *
     * @param \Symfony\Component\Routing\RequestContext $context The context
     */
    public function setContext(RequestContext $context)
    {
        $this->requestContext = $context;
        $this->generator->setRequestContext($context);
    }

    /**
     * Gets the request context.
     *
     * @return \Symfony\Component\Routing\RequestContext The context
     */
    public function getContext()
    {
        return $this->requestContext;
    }

    /**
     * Tries to match a URL path with a set of routes.
     *
     * If the matcher can not find information, it must throw one of the exceptions documented
     * below.
     *
     * @param string $pathinfo The path info to be parsed (raw format, i.e. not urldecoded)
     *
     * @return array An array of parameters
     *
     * @throws \Symfony\Component\Routing\Exception\ResourceNotFoundException If the resource could not be found
     * @throws \Symfony\Component\Routing\Exception\MethodNotAllowedException If the resource was found but the request method is not allowed
     */
    public function match($pathinfo)
    {
        throw new RuntimeException("The ContentUrlAliasRouter doesn't support the match() method.");
    }

    /**
     * Whether this generator supports the supplied $name.
     *
     * This check does not need to look if the specific instance can be
     * resolved to a route, only whether the router can generate routes from
     * objects of this class.
     *
     * @param mixed $name The route "name" which may also be an object or anything
     *
     * @return bool
     */
    public function supports($name)
    {
        return $name instanceof Content || $name instanceof ContentInfo;
    }

    /**
     * Convert a route identifier (name, content object etc) into a string
     * usable for logging and other debug/error messages.
     *
     * @param mixed $name
     * @param array $parameters which should contain a content field containing a RouteReferrersReadInterface object
     *
     * @return string
     */
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
