<?php
/**
 * File containing the LegacyResponseManager class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version 2014.07.0
 */

namespace Netgen\Bundle\MoreBundle\LegacyResponse;

use eZ\Bundle\EzPublishLegacyBundle\LegacyResponse;
use eZ\Bundle\EzPublishLegacyBundle\LegacyResponse\LegacyResponseManager as BaseLegacyResponseManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Templating\EngineInterface;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use ezpKernelResult;

/**
 * Utility class to manage Response from legacy controllers, map headers...
 */
class LegacyResponseManager extends BaseLegacyResponseManager implements ContainerAwareInterface
{
    /**
     * @var \Symfony\Component\Templating\EngineInterface
     */
    protected $templateEngine;

    /**
     * Template declaration to wrap legacy responses in a Twig pagelayout (optional)
     * Either a template declaration string or null/false to use legacy pagelayout
     * Default is null.
     *
     * @var string|null
     */
    protected $legacyLayout;

    /**
     * Flag indicating if we're running in legacy mode or not.
     *
     * @var bool
     */
    protected $legacyMode;

    /**
     * Container
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * Constructor
     *
     * @param \Symfony\Component\Templating\EngineInterface $templateEngine
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     */
    public function __construct( EngineInterface $templateEngine, ConfigResolverInterface $configResolver )
    {
        $this->templateEngine = $templateEngine;
        $this->legacyLayout = $configResolver->getParameter( 'module_default_layout', 'ezpublish_legacy' );
        $this->legacyMode = $configResolver->getParameter( 'legacy_mode' );
    }

    /**
     * Sets the Container.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container A ContainerInterface instance or null
     */
    public function setContainer( ContainerInterface $container = null )
    {
        $this->container = $container;
    }

    /**
     * Generates LegacyResponse object from result returned by legacy kernel.
     *
     * @param \ezpKernelResult $result
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @return \eZ\Bundle\EzPublishLegacyBundle\LegacyResponse
     */
    public function generateResponseFromModuleResult( ezpKernelResult $result )
    {
        $moduleResult = $result->getAttribute( 'module_result' );
        $pageLayout = $this->container->get( 'netgen_more.component.page_layout' );
        $request = $this->container->get( 'request' );

        if ( isset( $this->legacyLayout ) && !$this->legacyMode && !isset( $moduleResult['pagelayout'] ) )
        {
            // Replace original module_result content by filtered one
            $moduleResult['content'] = $result->getContent();

            $response = $this->render(
                $this->legacyLayout,
                array( 'module_result' => $moduleResult ) + $pageLayout->getParams( 0, $request->getPathInfo() )
            );

            $response->setModuleResult( $moduleResult );
        }
        else
        {
            $response = new LegacyResponse( $result->getContent() );
        }

        // Handling error codes sent by the legacy stack
        if ( isset( $moduleResult['errorCode'] ) )
        {
            // If having an "Unauthorized" or "Forbidden" error code in non-legacy mode,
            // we send an AccessDeniedException to be able to trigger redirection to login in Symfony stack.
            if ( !$this->legacyMode && ( $moduleResult['errorCode'] == 401 || $moduleResult['errorCode'] == 403 ) )
            {
                $errorMessage = isset( $moduleResult['errorMessage'] ) ? $moduleResult['errorMessage'] : 'Access denied';
                throw new AccessDeniedException( $errorMessage );
            }

            $response->setStatusCode(
                $moduleResult['errorCode'],
                isset( $moduleResult['errorMessage'] ) ? $moduleResult['errorMessage'] : null
            );
        }

        return $response;
    }

    /**
     * Renders a view and returns a Response.
     *
     * @param string $view The view name
     * @param array $parameters An array of parameters to pass to the view
     *
     * @return \eZ\Bundle\EzPublishLegacyBundle\LegacyResponse A LegacyResponse instance
     */
    protected function render( $view, array $parameters = array() )
    {
        $response = new LegacyResponse();
        $response->setContent( $this->templateEngine->render( $view, $parameters ) );
        return $response;
    }
}
