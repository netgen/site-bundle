<?php

namespace Netgen\Bundle\MoreBundle\LegacyResponse;

use eZ\Bundle\EzPublishLegacyBundle\LegacyResponse\LegacyResponseManager as BaseLegacyResponseManager;
use eZ\Bundle\EzPublishLegacyBundle\LegacyResponse;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Templating\EngineInterface;
use DateTime;
use ezpKernelResult;
use ezpKernelRedirect;

/**
 * Note changes over original class:
 *  - added optional handling of error responses through Symfony stack
 *
 * Utility class to manage Response from legacy controllers, map headers...
 */
class LegacyResponseManager extends BaseLegacyResponseManager
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
     * Flag indicating if we want to handle Legacy Stack error responses through Symfony exceptions
     *
     * @var bool
     */
    private $handleErrorResult;

    public function __construct( EngineInterface $templateEngine, ConfigResolverInterface $configResolver )
    {
        $this->templateEngine = $templateEngine;
        $this->legacyLayout = $configResolver->getParameter( 'module_default_layout', 'ezpublish_legacy' );
        $this->legacyMode = $configResolver->getParameter( 'legacy_mode' );
        $this->handleErrorResult = $configResolver->getParameter( 'content_view.handle_legacy_fallback_error_result', 'ngmore' );
    }

    /**
     * Generates LegacyResponse object from result returned by legacy kernel.
     *
     * @param \ezpKernelResult $result
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @return \eZ\Bundle\EzPublishLegacyBundle\LegacyResponse
     */
    public function generateResponseFromModuleResult( ezpKernelResult $result )
    {
        $moduleResult = $result->getAttribute( 'module_result' );

        // Handling error codes sent by the legacy stack
        if ( isset( $moduleResult['errorCode'] ) && $this->handleErrorResult && !$this->legacyMode )
        {
            // If having an "Unauthorized" or "Forbidden" error code in non-legacy mode,
            // we send an AccessDeniedException to be able to trigger redirection to login in Symfony stack.
            if ( $moduleResult['errorCode'] == 401 || $moduleResult['errorCode'] == 403 )
            {
                $errorMessage = isset( $moduleResult['errorMessage'] ) ? $moduleResult['errorMessage'] : 'Access denied';
                throw new AccessDeniedException( $errorMessage );
            }

            // If having an "Not Found" error code in non-legacy mode,
            // we send an NotFoundHttpException to be able to render 404 page in Symfony stack.
            if ( $moduleResult['errorCode'] == 404 )
            {
                $errorMessage = isset( $moduleResult['errorMessage'] ) ? $moduleResult['errorMessage'] : 'Not Found';
                throw new NotFoundHttpException( $errorMessage );
            }
        }

        if ( isset( $this->legacyLayout ) && !$this->legacyMode && !isset( $moduleResult['pagelayout'] ) )
        {
            // Replace original module_result content by filtered one
            $moduleResult['content'] = $result->getContent();

            $response = $this->render(
                $this->legacyLayout,
                array( 'module_result' => $moduleResult )
            );

            $response->setModuleResult( $moduleResult );
        }
        else
        {
            $response = new LegacyResponse( $result->getContent() );
        }

        // Map status code
        if ( isset( $moduleResult['errorCode'] ) )
        {
            $response->setStatusCode(
                $moduleResult['errorCode'],
                isset( $moduleResult['errorMessage'] ) ? $moduleResult['errorMessage'] : null
            );
        }

        return $response;
    }

    /**
     * Generates proper RedirectResponse from $redirectResult.
     *
     * @param \ezpKernelRedirect $redirectResult
     *
     * @return RedirectResponse
     */
    public function generateRedirectResponse( ezpKernelRedirect $redirectResult )
    {
        // Remove duplicate Location header.
        $this->removeHeader( 'location' );
        return new RedirectResponse( $redirectResult->getTargetUrl(), $redirectResult->getStatusCode() );
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

    /**
     * Maps headers sent by the legacy stack to $response.
     *
     * @param array $headers Array headers.
     * @param \Symfony\Component\HttpFoundation\Response $response
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function mapHeaders( array $headers, Response $response )
    {
        foreach ( $headers as $header )
        {
            $headerArray = explode( ": ", $header, 2 );
            $headerName = strtolower( $headerArray[0] );
            $headerValue = $headerArray[1];
            // Removing existing header to avoid duplicate values
            $this->removeHeader( $headerName );

            switch ( $headerName )
            {
                // max-age and s-maxage are skipped because they are values of the cache-control header
                case "etag":
                    $response->setEtag( $headerValue );
                    break;
                case "last-modified":
                    $response->setLastModified( new DateTime( $headerValue ) );
                    break;
                case "expires":
                    $response->setExpires( new DateTime( $headerValue ) );
                    break;
                default;
                    $response->headers->set( $headerName, $headerValue, true );
                    break;
            }
        }

        return $response;
    }

    /**
     * Wraps header_remove() function.
     * This is mainly to isolate it and become testable.
     *
     * @param string $headerName
     */
    protected function removeHeader( $headerName )
    {
        if ( !headers_sent() )
        {
            header_remove( $headerName );
        }
    }
}
