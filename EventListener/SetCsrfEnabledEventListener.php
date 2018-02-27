<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

class SetCsrfEnabledEventListener implements EventSubscriberInterface
{
    /**
     * @var \Symfony\Component\Security\Csrf\CsrfTokenManager
     */
    protected $csrfTokenManager;

    /**
     * Constructor.
     *
     * @param \Symfony\Component\Security\Csrf\CsrfTokenManager $csrfTokenManager
     */
    public function __construct(CsrfTokenManager $csrfTokenManager = null)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(KernelEvents::REQUEST => 'onKernelRequest');
    }

    /**
     * Sets the variable into request indicating if CSRF protection is enabled or not.
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $event->getRequest()->attributes->set(
            'csrf_enabled',
            $this->csrfTokenManager instanceof CsrfTokenManager
        );
    }
}
