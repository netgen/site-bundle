<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\EventListener;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Routing\UrlAliasRouter;
use Ibexa\Core\MVC\Symfony\View\View;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use function is_string;
use function mb_strpos;
use function sprintf;

class NoViewTemplateEventListener implements EventSubscriberInterface
{
    protected UrlGeneratorInterface $urlGenerator;

    protected ConfigResolverInterface $configResolver;

    protected bool $enabled = true;

    public function __construct(UrlGeneratorInterface $urlGenerator, ConfigResolverInterface $configResolver)
    {
        $this->urlGenerator = $urlGenerator;
        $this->configResolver = $configResolver;
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => 'getController'];
    }

    /**
     * Enables or disables redirection to the frontpage
     * if no full view template exists for content or location.
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * Redirects to the frontpage for any full view that does not have a template configured.
     */
    public function getController(ControllerEvent $event): void
    {
        if (!$this->enabled || $event->getRequestType() !== HttpKernelInterface::MAIN_REQUEST) {
            return;
        }

        $request = $event->getRequest();

        $view = $request->attributes->get('view');
        if (!$view instanceof View || $view->getViewType() !== 'full') {
            return;
        }

        if (is_string($view->getTemplateIdentifier())) {
            return;
        }

        if (
            $view->getControllerReference() instanceof ControllerReference
            && mb_strpos($view->getControllerReference()->controller, sprintf('%s::', RedirectController::class)) === 0
        ) {
            return;
        }

        $event->setController(
            function (): RedirectResponse {
                $rootLocationId = $this->configResolver->getParameter('content.tree_root.location_id');

                return new RedirectResponse(
                    $this->urlGenerator->generate(UrlAliasRouter::URL_ALIAS_ROUTE_NAME, ['locationId' => $rootLocationId]),
                    RedirectResponse::HTTP_MOVED_PERMANENTLY,
                );
            },
        );
    }
}
