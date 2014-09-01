<?php

namespace Netgen\Bundle\MoreBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Exception;
use Closure;
use eZDebug;

class LegacyDebugDataCollector extends DataCollector
{
    /**
     * @var \Closure
     */
    protected $legacyKernel;

    /**
     * Constructor
     *
     * @param \Closure $legacyKernel
     */
    public function __construct( Closure $legacyKernel )
    {
        $this->legacyKernel = $legacyKernel;
    }

    /**
     * Collects data for the given Request and Response.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request A Request instance
     * @param \Symfony\Component\HttpFoundation\Response $response A Response instance
     * @param \Exception $exception An Exception instance
     */
    public function collect( Request $request, Response $response, Exception $exception = null )
    {
        $this->data['legacyDebug'] = $this->getLegacyDebugData();
    }

    /**
     * Returns the legacy debug output
     *
     * @return string
     */
    public function getLegacyDebug()
    {
        return $this->data['legacyDebug'];
    }

    /**
     * Returns the name of the collector.
     *
     * @return string The collector name
     */
    public function getName()
    {
        return "ngmore_legacy_debug";
    }

    /**
     * Returns the legacy debug output
     *
     * @return string
     */
    protected function getLegacyDebugData()
    {
        $legacyKernel = $this->legacyKernel;
        return $legacyKernel()->runCallback(
            function()
            {
                return eZDebug::instance()->printReportInternal();
            }
        );
    }
}
