<?php

namespace Netgen\Bundle\MoreBundle\EventListener;

use eZ\Publish\Core\FieldType\Page\PageService;
use eZ\Publish\Core\FieldType\Page\Parts\Block;
use eZ\Publish\Core\MVC\Symfony\Controller\ManagerInterface as ControllerManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use eZ\Publish\Core\Base\Exceptions\UnauthorizedException;
use Psr\Log\LoggerInterface;

class BlockViewControllerListener implements EventSubscriberInterface
{
    /**
     * @var \eZ\Publish\Core\MVC\Symfony\Controller\ManagerInterface
     */
    private $controllerManager;

    /**
     * @var \Symfony\Component\HttpKernel\Controller\ControllerResolverInterface
     */
    private $controllerResolver;

    /**
     * @var \eZ\Publish\Core\FieldType\Page\PageService
     */
    private $pageService;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param \Symfony\Component\HttpKernel\Controller\ControllerResolverInterface $controllerResolver
     * @param \eZ\Publish\Core\MVC\Symfony\Controller\ManagerInterface $controllerManager
     * @param \eZ\Publish\Core\FieldType\Page\PageService $pageService
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        ControllerResolverInterface $controllerResolver,
        ControllerManagerInterface $controllerManager,
        PageService $pageService,
        LoggerInterface $logger
    )
    {
        $this->controllerManager = $controllerManager;
        $this->controllerResolver = $controllerResolver;
        $this->pageService = $pageService;
        $this->logger = $logger;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return array( KernelEvents::CONTROLLER => 'getController' );
    }

    /**
     * Detects if there is a custom controller to use to render a Block.
     *
     * @param \Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function getController( FilterControllerEvent $event )
    {
        $request = $event->getRequest();

        // Only taking block related controllers (i.e. ez_page:viewBlock)
        if ( strpos( $request->attributes->get( '_controller' ), 'ez_page:' ) === false )
        {
            return;
        }

        try
        {
            $valueObject = null;
            if ( $request->attributes->has( 'id' ) )
            {
                $valueObject = $this->pageService->loadBlock( $request->attributes->get( 'id' ) );
                $request->attributes->set( 'block', $valueObject );
            }
            else if ( $request->attributes->get( 'block' ) instanceof Block )
            {
                $valueObject = $request->attributes->get( 'block' );
                $request->attributes->set( 'id', $valueObject->id );
            }
        }
        catch ( UnauthorizedException $e )
        {
            throw new AccessDeniedException();
        }

        if ( $valueObject === null )
        {
            $this->logger->error( 'Could not resolve a block view controller, invalid value object to match.' );
            return;
        }

        $controllerReference = $this->controllerManager->getControllerReference( $valueObject, 'block' );
        if ( !$controllerReference instanceof ControllerReference )
        {
            return;
        }

        $request->attributes->set( '_controller', $controllerReference->controller );
        $event->setController( $this->controllerResolver->getController( $request ) );
    }
}
