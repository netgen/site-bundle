<?php

namespace Netgen\Bundle\MoreBundle\EventListener;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\MVC\Symfony\View\CachableView;
use eZ\Publish\Core\MVC\Symfony\View\BlockView;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class BlockCacheResponseListener implements EventSubscriberInterface
{
    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var bool
     */
    protected $enableViewCache;

    /**
     * Constructor
     *
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     */
    public function __construct( ConfigResolverInterface $configResolver )
    {
        $this->configResolver = $configResolver;
    }

    /**
     * Sets if view cache is enabled
     *
     * @param bool $enableViewCache
     */
    public function setEnableViewCache( $enableViewCache )
    {
        $this->enableViewCache = $enableViewCache;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return array( KernelEvents::RESPONSE => array( 'configureBlockCache', -20 ) );
    }

    /**
     * Configures the block cache settings
     *
     * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
     */
    public function configureBlockCache( FilterResponseEvent $event )
    {
        $view = $event->getRequest()->attributes->get( 'view' );
        if ( !$view instanceof BlockView || !$view instanceof CachableView )
        {
            return;
        }

        if ( !$this->enableViewCache || !$view->isCacheEnabled() )
        {
            return;
        }

        $response = $event->getResponse();
        $response->setPublic();

        $blockType = strtolower( $view->getBlock()->type );
        $cacheSettings = $event->getRequest()->attributes->get( 'cacheSettings', array() );

        if ( !isset( $cacheSettings['smax-age'] ) )
        {
            if ( $this->configResolver->hasParameter( 'block_settings.' . $blockType . '.shared_max_age', 'ngmore' ) )
            {
                $cacheSettings['smax-age'] = (int)$this->configResolver->getParameter( 'block_settings.' . $blockType . '.shared_max_age', 'ngmore' );
                $response->setSharedMaxAge( $cacheSettings['smax-age'] );
            }
        }

        if ( !isset( $cacheSettings['max-age'] ) )
        {
            if ( $this->configResolver->hasParameter( 'block_settings.' . $blockType . '.max_age', 'ngmore' ) )
            {
                $cacheSettings['max-age'] = (int)$this->configResolver->getParameter( 'block_settings.' . $blockType . '.max_age', 'ngmore' );
                $response->setMaxAge( $cacheSettings['max-age'] );
            }
        }

        $event->getRequest()->attributes->set( 'cacheSettings', $cacheSettings );
    }
}
