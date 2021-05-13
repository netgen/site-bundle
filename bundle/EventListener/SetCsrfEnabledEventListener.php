<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

class SetCsrfEnabledEventListener implements EventSubscriberInterface
{
    protected ?CsrfTokenManager $csrfTokenManager;

    public function __construct(?CsrfTokenManager $csrfTokenManager = null)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }

    /**
     * Sets the variable into request indicating if CSRF protection is enabled or not.
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        $event->getRequest()->attributes->set(
            'csrf_enabled',
            $this->csrfTokenManager instanceof CsrfTokenManager,
        );
    }
}
