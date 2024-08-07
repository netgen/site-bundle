<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller\User;

use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Netgen\Bundle\SiteBundle\Controller\Controller;
use Netgen\Bundle\SiteBundle\Event\SiteEvents;
use Netgen\Bundle\SiteBundle\Event\User as UserEvents;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ActivationForm extends Controller
{
    public function __construct(
        private UserService $userService,
        private EventDispatcherInterface $eventDispatcher,
        private ConfigResolverInterface $configResolver,
    ) {}

    /**
     * Displays and validates the form for sending an activation mail.
     */
    public function __invoke(Request $request): Response
    {
        $form = $this->createActivationForm();
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render(
                $this->configResolver->getParameter('template.user.activate', 'ngsite'),
                [
                    'form' => $form->createView(),
                ],
            );
        }

        /** @var \Ibexa\Contracts\Core\Repository\Values\User\User[] $users */
        $users = $this->userService->loadUsersByEmail($form->get('email')->getData());

        $activationRequestEvent = new UserEvents\ActivationRequestEvent(
            $form->get('email')->getData(),
            $users[0] ?? null,
        );

        $activationRequestEvent->setParameter('form', $form);

        $this->eventDispatcher->dispatch($activationRequestEvent, SiteEvents::USER_ACTIVATION_REQUEST);

        return $this->render(
            $this->configResolver->getParameter('template.user.activate_sent', 'ngsite'),
        );
    }

    /**
     * Creates activation form.
     */
    private function createActivationForm(): FormInterface
    {
        return $this->createFormBuilder(null, ['translation_domain' => 'ngsite_user'])
            ->add(
                'email',
                EmailType::class,
                [
                    'constraints' => [
                        new Constraints\Email(),
                        new Constraints\NotBlank(),
                    ],
                ],
            )->getForm();
    }
}
