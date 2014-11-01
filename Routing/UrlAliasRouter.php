<?php

namespace Netgen\Bundle\MoreBundle\Routing;

use eZ\Bundle\EzPublishCoreBundle\Routing\UrlAliasRouter as BaseUrlAliasRouter;

use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\URLAliasService;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\Core\MVC\Symfony\Routing\Generator\UrlAliasGenerator;
use Symfony\Component\Routing\RequestContext;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use LogicException;

class UrlAliasRouter extends BaseUrlAliasRouter
{
    const ROUTE_NAME = 'ngmore_urlalias';

    /**
     * @var \eZ\Publish\API\Repository\ContentService
     */
    protected $contentService;

    /**
     * Constructor
     *
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     * @param \eZ\Publish\API\Repository\URLAliasService $urlAliasService
     * @param \eZ\Publish\API\Repository\ContentService $contentService
     * @param \eZ\Publish\Core\MVC\Symfony\Routing\Generator\UrlAliasGenerator $generator
     * @param \Symfony\Component\Routing\RequestContext $requestContext
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        LocationService $locationService,
        URLAliasService $urlAliasService,
        ContentService $contentService,
        UrlAliasGenerator $generator,
        RequestContext $requestContext,
        LoggerInterface $logger = null
    )
    {
        parent::__construct( $locationService, $urlAliasService, $generator, $requestContext, $logger );

        $this->contentService = $contentService;
    }

    /**
     * Generates a URL for a location, from the given parameters.
     *
     * It is possible to directly pass a Location object as the route name, as the ChainRouter allows it through ChainedRouterInterface.
     *
     * If $name is a route name, the "location" or "content" key in $parameters must be set to a valid
     * eZ\Publish\API\Repository\Values\Content\Location, eZ\Publish\API\Repository\Values\Content\Content
     * or eZ\Publish\API\Repository\Values\Content\ContentInfo object.
     *
     * "locationId" or "contentId" can also be provided.
     *
     * If the generator is not able to generate the url, it must throw the RouteNotFoundException
     * as documented below.
     *
     * @param string|\eZ\Publish\API\Repository\Values\Content\Location $name The name of the route or a Location instance
     * @param mixed $parameters An array of parameters
     * @param boolean $absolute Whether to generate an absolute URL
     *
     * @throws \LogicException
     * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException
     * @throws \InvalidArgumentException
     *
     * @return string The generated URL
     */
    public function generate( $name, $parameters = array(), $absolute = false )
    {
        if ( isset( $parameters['location'] ) || isset( $parameters['locationId'] ) )
        {
            // Check if location is a valid Location object
            if ( isset( $parameters['location'] ) && !$parameters['location'] instanceof Location )
            {
                throw new LogicException(
                    "When generating a Netgen More UrlAlias route, 'location' parameter must be a valid eZ\\Publish\\API\\Repository\\Values\\Content\\Location."
                );
            }

            $location = isset( $parameters['location'] ) ? $parameters['location'] : $this->locationService->loadLocation( $parameters['locationId'] );
            unset( $parameters['location'], $parameters['locationId'], $parameters['viewType'], $parameters['layout'] );
            return $this->generator->generate( $location, $parameters, $absolute );
        }

        if ( isset( $parameters['content'] ) || isset( $parameters['contentId'] ) )
        {
            // Check if content is a valid Content or ContentInfo object
            if ( isset( $parameters['content'] ) &&
                 !$parameters['content'] instanceof Content && !$parameters['content'] instanceof ContentInfo )
            {
                throw new LogicException(
                    sprintf(
                        "When generating a Netgen More UrlAlias route, 'content' parameter must be a valid %s or %s.",
                        "eZ\\Publish\\API\\Repository\\Values\\Content\\Content",
                        "eZ\\Publish\\API\\Repository\\Values\\Content\\ContentInfo"
                    )
                );
            }

            $content = isset( $parameters['content'] ) ?
                $parameters['content'] :
                $this->contentService->loadContentInfo( $parameters['contentId'] );

            unset( $parameters['content'], $parameters['contentId'], $parameters['viewType'], $parameters['layout'] );

            return $this->generator->generate(
                $this->locationService->loadLocation(
                    $content instanceof Content ? $content->contentInfo->mainLocationId : $content->mainLocationId
                ),
                $parameters,
                $absolute
            );
        }

        throw new InvalidArgumentException(
            "When generating a Netgen More UrlAlias route, either 'location', 'locationId', 'content' or 'contentId' must be provided."
        );
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
    public function supports( $name )
    {
        return $name === self::ROUTE_NAME;
    }
}
