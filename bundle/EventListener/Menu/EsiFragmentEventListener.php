<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\EventListener\Menu;

use Netgen\Bundle\SiteBundle\Event\Menu\LocationMenuItemEvent;
use Netgen\Bundle\SiteBundle\Event\SiteEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use function rawurldecode;

final class EsiFragmentEventListener implements EventSubscriberInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private string $fragmentPath,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [SiteEvents::MENU_LOCATION_ITEM => 'onMenuItemBuild'];
    }

    public function onMenuItemBuild(LocationMenuItemEvent $event): void
    {
        $request = $this->requestStack->getMainRequest();
        if (!$request instanceof Request || $this->fragmentPath !== rawurldecode($request->getPathInfo())) {
            return;
        }

        if (!$request->attributes->has('activeItemId')) {
            return;
        }

        if ($event->location->id === (int) $request->attributes->get('activeItemId')) {
            $event->item->setCurrent(true);
        }
    }
}
