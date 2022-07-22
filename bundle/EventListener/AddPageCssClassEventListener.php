<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\EventListener;

use eZ\Publish\Core\FieldType\TextLine\Value as TextLineValue;
use eZ\Publish\Core\MVC\Symfony\View\Event\FilterViewParametersEvent;
use eZ\Publish\Core\MVC\Symfony\View\ViewEvents;
use Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentValueView;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function trim;

class AddPageCssClassEventListener implements EventSubscriberInterface
{
    /**
     * Returns an array of event names this subscriber wants to listen to.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ViewEvents::FILTER_VIEW_PARAMETERS => 'addPageCssParameter',
        ];
    }

    /**
     * Injects the used view type into the content view template.
     */
    public function addPageCssParameter(FilterViewParametersEvent $event): void
    {
        $view = $event->getView();

        if (!$view instanceof ContentValueView || $view->getViewType() !== 'full') {
            return;
        }

        $content = $view->getSiteContent();
        if (!$content->hasField('css_class') || $content->getField('css_class')->isEmpty()) {
            return;
        }

        $fieldValue = $content->getField('css_class')->value;
        if (!$fieldValue instanceof TextLineValue) {
            return;
        }

        $event->getParameterBag()->set('page_css_class', trim($fieldValue->text));
    }
}
