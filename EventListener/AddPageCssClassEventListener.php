<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\EventListener;

use eZ\Publish\Core\FieldType\TextLine\Value as TextLineValue;
use eZ\Publish\Core\MVC\Symfony\Event\PreContentViewEvent;
use eZ\Publish\Core\MVC\Symfony\MVCEvents;
use Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentValueView;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AddPageCssClassEventListener implements EventSubscriberInterface
{
    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    protected $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            MVCEvents::PRE_CONTENT_VIEW => 'onPreContentView',
        ];
    }

    /**
     * Injects the used view type into the content view template.
     */
    public function onPreContentView(PreContentViewEvent $event): void
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        if (!$currentRequest instanceof Request) {
            return;
        }

        $view = $currentRequest->attributes->get('view');
        if (!$view instanceof ContentValueView) {
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

        $event->getContentView()->addParameters(
            [
                'page_css_class' => trim($fieldValue->text),
            ]
        );
    }
}
