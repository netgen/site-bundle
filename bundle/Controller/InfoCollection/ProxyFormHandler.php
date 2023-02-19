<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller\InfoCollection;

use Netgen\Bundle\IbexaSiteApiBundle\View\ContentView;
use Netgen\Bundle\SiteBundle\Controller\Controller;
use Netgen\Bundle\SiteBundle\InfoCollection\RefererResolver;
use Netgen\IbexaSiteApi\API\Values\Location;
use Netgen\InformationCollection\API\Events;
use Netgen\InformationCollection\API\Service\CaptchaService;
use Netgen\InformationCollection\API\Value\Event\InformationCollected;
use Netgen\InformationCollection\Handler;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

use function array_merge;

/**
 * Note that this is the main action for handling forms, but it doesn't have its
 * own route. Instead, it's "proxied" through the content view, relying on the main
 * request for its data.
 */
final class ProxyFormHandler extends Controller
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly CaptchaService $captchaService,
        private readonly Handler $handler,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RefererResolver $refererResolver,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function __invoke(ContentView $view): ContentView
    {
        $request = $this->requestStack->getMainRequest();

        if ($request === null) {
            throw new RuntimeException('Missing request');
        }

        $location = $view->getSiteLocation();

        if ($location === null) {
            throw new RuntimeException('Missing location context');
        }

        $refererLocationId = null;

        if ($view->hasParameter('refererLocationId')) {
            $refererLocationId = $view->getParameter('refererLocationId');
        }

        $view->addParameters(
            array_merge(
                $this->collectInformation($location, $request),
                [
                    'content' => $location->content,
                    'location' => $location,
                    'view' => $view,
                    'referer' => $this->refererResolver->getReferer($refererLocationId),
                ],
            ),
        );

        $view->setCacheEnabled(false);

        return $view;
    }

    private function collectInformation(Location $location, Request $request): array
    {
        $form = $this->handler->getForm(
            $location->innerLocation->getContent(),
            $location->innerLocation,
            $request,
        );

        $isCollected = false;
        $captcha = $this->captchaService->getCaptcha($location->innerLocation);

        $form->handleRequest($request);
        $formSubmitted = $form->isSubmitted();
        $validCaptcha = $captcha->isValid($request);

        if ($formSubmitted && $form->isValid() && $validCaptcha) {
            $event = new InformationCollected($form->getData(), [], ['form' => $form]);
            $this->eventDispatcher->dispatch($event, Events::INFORMATION_COLLECTED);

            $isCollected = true;
        }

        if ($formSubmitted && !$form->isValid()) {
            $this->logger->critical((string) $form->getErrors(true, false));
        }

        if ($formSubmitted && !$validCaptcha) {
            $this->logger->critical('Captcha failed');

            $form->addError(new FormError('Captcha failed'));
        }

        return [
            'is_collected' => $isCollected,
            'form' => $form->createView(),
        ];
    }
}
