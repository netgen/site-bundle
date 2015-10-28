<?php

namespace Netgen\Bundle\MoreBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\MVC\Symfony\View\Event\FilterViewBuilderParametersEvent;
use eZ\Publish\Core\MVC\Symfony\View\ViewEvents;

class InvisibleLocationsListener implements EventSubscriberInterface
{
    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * @var bool
     */
    protected $showInvisibleLocations = false;

    /**
     * Constructor
     *
     * @param \eZ\Publish\API\Repository\Repository $repository
     */
    public function __construct( Repository $repository )
    {
        $this->repository = $repository;
    }

    /**
     * Sets if invisible locations should be shown
     *
     * @param bool $showInvisibleLocations
     */
    public function setShowInvisibleLocations( $showInvisibleLocations = false )
    {
        $this->showInvisibleLocations = (bool)$showInvisibleLocations;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        // Priority must be below zero to execute after the original RequestAttributes listener
        return array(
            ViewEvents::FILTER_BUILDER_PARAMETERS => array( 'onFilterBuilderParameters', -10 )
        );
    }

    /**
     * Injects the invisible location if configured so in parameters
     *
     * @param \eZ\Publish\Core\MVC\Symfony\View\Event\FilterViewBuilderParametersEvent $event
     */
    public function onFilterBuilderParameters( FilterViewBuilderParametersEvent $event )
    {
        $parameters = $event->getParameters();
        if ( !$this->showInvisibleLocations || !$parameters->has( 'locationId' ) )
        {
            return;
        }

        $location = $this->repository->getLocationService()->loadLocation(
            $parameters->get( 'locationId' )
        );

        $parameters->set( 'location', $location );
        $parameters->remove( 'locationId' );
    }
}
