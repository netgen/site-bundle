<?php

namespace Netgen\Bundle\MoreBundle\Core\MVC\Symfony\View\Builder;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\MVC\Symfony\View\Configurator;
use eZ\Publish\Core\MVC\Symfony\View\Builder\ContentViewBuilder;
use eZ\Publish\Core\MVC\Symfony\View\ParametersInjector;

class InvisibleContentViewBuilder extends ContentViewBuilder
{
    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * Constructor
     *
     * @param \eZ\Publish\API\Repository\Repository $repository
     * @param \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface $authorizationChecker
     * @param \eZ\Publish\Core\MVC\Symfony\View\Configurator $viewConfigurator
     * @param \eZ\Publish\Core\MVC\Symfony\View\ParametersInjector $viewParametersInjector
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     */
    public function __construct(
        Repository $repository,
        AuthorizationCheckerInterface $authorizationChecker,
        Configurator $viewConfigurator,
        ParametersInjector $viewParametersInjector,
        ConfigResolverInterface $configResolver
    )
    {
        parent::__construct(
            $repository,
            $authorizationChecker,
            $viewConfigurator,
            $viewParametersInjector
        );

        $this->repository = $repository;
        $this->configResolver = $configResolver;
    }

    /**
     * Tests if the builder matches the given argument.
     *
     * @param mixed $argument Anything the builder can decide against. Example: a controller's request string.
     *
     * @return bool true if the ViewBuilder matches the argument, false otherwise.
     */
    public function matches( $argument )
    {
        return parent::matches( $argument );
    }

    /**
     * Builds the View based on $parameters.
     *
     * @param array $parameters
     *
     * @return \eZ\Publish\Core\MVC\Symfony\View\View An implementation of the View interface
     */
    public function buildView( array $parameters )
    {
        if ( isset( $parameters['locationId'] ) )
        {
            $showInvisibleLocations = false;
            if ( $this->configResolver->hasParameter( 'content_view.show_invisible_locations', 'ngmore' ) )
            {
                $showInvisibleLocations = (bool)$this->configResolver->getParameter(
                    'content_view.show_invisible_locations',
                    'ngmore'
                );
            }

            if ( $showInvisibleLocations )
            {
                $location = $this->repository->getLocationService()->loadLocation(
                    $parameters['locationId']
                );

                $parameters = array( 'location' => $location ) + $parameters;
                unset( $parameters['locationId'] );
            }
        }

        return parent::buildView( $parameters );
    }
}
