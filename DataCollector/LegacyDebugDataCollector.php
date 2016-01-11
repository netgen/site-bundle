<?php

namespace Netgen\Bundle\MoreBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use RuntimeException;
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
     * Request matcher for user context hash requests.
     *
     * @var \Symfony\Component\HttpFoundation\RequestMatcherInterface
     */
    protected $userContextRequestMatcher;

    /**
     * Constructor.
     *
     * @param \Closure $legacyKernel
     * @param \Symfony\Component\HttpFoundation\RequestMatcherInterface $userContextRequestMatcher
     */
    public function __construct(Closure $legacyKernel, RequestMatcherInterface $userContextRequestMatcher)
    {
        $this->legacyKernel = $legacyKernel;
        $this->userContextRequestMatcher = $userContextRequestMatcher;
    }

    /**
     * Collects data for the given Request and Response.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request A Request instance
     * @param \Symfony\Component\HttpFoundation\Response $response A Response instance
     * @param \Exception $exception An Exception instance
     */
    public function collect(Request $request, Response $response, Exception $exception = null)
    {
        // Do not collect data if it's a user hash request
        if ($this->userContextRequestMatcher->matches($request)) {
            return;
        }

        $this->data['legacyDebug'] = $this->getLegacyDebugData();
    }

    /**
     * Returns the legacy debug output.
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
        return 'ngmore_legacy_debug';
    }

    /**
     * Returns the legacy debug output.
     *
     * @return string
     */
    protected function getLegacyDebugData()
    {
        $legacyKernel = $this->legacyKernel;

        try {
            return $legacyKernel()->runCallback(
                function () {
                    return eZDebug::instance()->printReportInternal();
                }
            );
        } catch (RuntimeException $e) {
            return '';
        }
    }
}
