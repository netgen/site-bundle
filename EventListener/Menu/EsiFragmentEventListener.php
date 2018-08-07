<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\EventListener\Menu;

use Netgen\Bundle\MoreBundle\Event\Menu\LocationMenuItemEvent;
use Netgen\Bundle\MoreBundle\Event\NetgenMoreEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class EsiFragmentEventListener implements EventSubscriberInterface
{
    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    protected $requestStack;

    /**
     * @var string
     */
    protected $fragmentPath;

    public function __construct(RequestStack $requestStack, string $fragmentPath)
    {
        $this->requestStack = $requestStack;
        $this->fragmentPath = $fragmentPath;
    }

    public static function getSubscribedEvents(): array
    {
        return [NetgenMoreEvents::MENU_LOCATION_ITEM => 'onMenuItemBuild'];
    }

    public function onMenuItemBuild(LocationMenuItemEvent $event): void
    {
        $request = $this->requestStack->getMasterRequest();
        if ($this->fragmentPath !== rawurldecode($request->getPathInfo())) {
            return;
        }

        if (!$request->attributes->has('activeItemId')) {
            return;
        }

        if ($event->getLocation()->id === (int) $request->attributes->get('activeItemId')) {
            $event->getItem()->setCurrent(true);
        }
    }
}
