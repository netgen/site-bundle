<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;
use Twig\Extension\StringLoaderExtension;

class TwigExtensionsListener implements EventSubscriberInterface
{
    /**
     * @var \Twig\Environment
     */
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }

    /**
     * Adds the Twig StringLoader extension to the environment if it doesn't already exist.
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        if ($this->twig->hasExtension(StringLoaderExtension::class)) {
            return;
        }

        $this->twig->addExtension(new StringLoaderExtension());
    }
}
