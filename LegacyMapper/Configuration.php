<?php

namespace Netgen\Bundle\MoreBundle\LegacyMapper;

use eZ\Publish\Core\MVC\Legacy\LegacyEvents;
use eZ\Publish\Core\MVC\Legacy\Event\PreBuildKernelEvent;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Configuration extends ContainerAware implements EventSubscriberInterface
{
    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var array
     */
    protected $options;

    public function __construct( ConfigResolverInterface $configResolver, array $options = array() )
    {
        $this->configResolver = $configResolver;
        $this->options = $options;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            LegacyEvents::PRE_BUILD_LEGACY_KERNEL => array( 'onBuildKernel', 0 )
        );
    }

    /**
     * Adds settings to the parameters that will be injected into the legacy kernel
     *
     * @param \eZ\Publish\Core\MVC\Legacy\Event\PreBuildKernelEvent $event
     */
    public function onBuildKernel( PreBuildKernelEvent $event )
    {
        $settings = array();

        $enabledLegacySettings = isset( $this->options['enabled_legacy_settings'] ) &&
            is_array( $this->options['enabled_legacy_settings'] ) ?
                $this->options['enabled_legacy_settings'] :
                array();

        foreach ( $enabledLegacySettings as $legacyIniName )
        {
            if ( !$this->configResolver->hasParameter( $legacyIniName, 'ngmore_legacy' ) )
            {
                continue;
            }

            $legacyIni = $this->configResolver->getParameter( $legacyIniName, 'ngmore_legacy' );
            if ( !is_array( $legacyIni ) )
            {
                continue;
            }

            foreach ( $legacyIni as $legacyIniSection => $legacyIniConfig )
            {
                if ( !is_string( $legacyIniSection ) || !is_array( $legacyIniConfig ) )
                {
                    continue;
                }

                foreach ( $legacyIniConfig as $legacyIniValueName => $legacyIniValue )
                {
                    if ( !is_string( $legacyIniValueName ) )
                    {
                        continue;
                    }

                    $settings[$legacyIniName . '.ini/' . $legacyIniSection . '/' . $legacyIniValueName] = $legacyIniValue;
                }
            }
        }

        $event->getParameters()->set(
            'injected-settings',
            $settings + (array)$event->getParameters()->get( 'injected-settings' )
        );
    }
}
