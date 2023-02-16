<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\EventListener;

use Ibexa\Core\MVC\Symfony\View\Event\FilterViewParametersEvent;
use Ibexa\Core\MVC\Symfony\View\ViewEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AddViewTypeEventListener implements EventSubscriberInterface
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ViewEvents::FILTER_VIEW_PARAMETERS => 'addViewTypeParameter',
        ];
    }

    /**
     * Injects the used view type into the content view template.
     */
    public function addViewTypeParameter(FilterViewParametersEvent $event): void
    {
        $event->getParameterBag()->set(
            'view_type',
            $event->getView()->getViewType(),
        );
    }
}
