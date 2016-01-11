<?php

namespace Netgen\Bundle\MoreBundle\LegacyMapper;

use eZ\Publish\Core\MVC\Legacy\LegacyEvents;
use eZ\Publish\Core\MVC\Legacy\Event\PreBuildKernelEvent;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Configuration implements EventSubscriberInterface
{
    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var array
     */
    protected $options;

    public function __construct(ConfigResolverInterface $configResolver, array $options = array())
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
            LegacyEvents::PRE_BUILD_LEGACY_KERNEL => array('onBuildKernel', 64),
        );
    }

    /**
     * Adds settings to the parameters that will be injected into the legacy kernel.
     *
     * @param \eZ\Publish\Core\MVC\Legacy\Event\PreBuildKernelEvent $event
     */
    public function onBuildKernel(PreBuildKernelEvent $event)
    {
        $injectedSettings = array();
        $injectedMergeSettings = array();

        $enabledLegacySettings = isset($this->options['enabled_legacy_settings']) &&
            is_array($this->options['enabled_legacy_settings']) ?
                $this->options['enabled_legacy_settings'] :
                array();

        $replaceArrayValues = isset($this->options['replace_array_values']) &&
            is_array($this->options['replace_array_values']) ?
                $this->options['replace_array_values'] :
                array();

        foreach ($enabledLegacySettings as $legacyIniName) {
            if (!$this->configResolver->hasParameter($legacyIniName, 'ngmore_legacy')) {
                continue;
            }

            $legacyIni = $this->configResolver->getParameter($legacyIniName, 'ngmore_legacy');
            if (!is_array($legacyIni)) {
                continue;
            }

            foreach ($legacyIni as $legacyIniSection => $legacyIniConfig) {
                if (!is_string($legacyIniSection) || !is_array($legacyIniConfig)) {
                    continue;
                }

                foreach ($legacyIniConfig as $legacyIniValueName => $legacyIniValue) {
                    if (!is_string($legacyIniValueName)) {
                        continue;
                    }

                    if (is_array($legacyIniValue) && empty($replaceArrayValues[$legacyIniName][$legacyIniSection][$legacyIniValueName])) {
                        $injectedMergeSettings[$legacyIniName . '.ini/' . $legacyIniSection . '/' . $legacyIniValueName] = $legacyIniValue;
                    } else {
                        // We need to manipulate the array config to conform to the format eZINI expects
                        if (is_array($legacyIniValue)) {
                            if (isset($legacyIniValue[0])) {
                                $legacyIniValue = array('') + array_combine(range(1, count($legacyIniValue)), $legacyIniValue);
                            } else {
                                $legacyIniValue = array('') + $legacyIniValue;
                            }
                        }

                        $injectedSettings[$legacyIniName . '.ini/' . $legacyIniSection . '/' . $legacyIniValueName] = $legacyIniValue;
                    }
                }
            }
        }

        $event->getParameters()->set(
            'injected-settings',
            $injectedSettings + (array)$event->getParameters()->get('injected-settings')
        );

        $event->getParameters()->set(
            'injected-merge-settings',
            $injectedMergeSettings + (array)$event->getParameters()->get('injected-merge-settings')
        );
    }
}
