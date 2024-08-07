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

final class ForgotPassword extends Controller
{
    public function __construct(
        private UserService $userService,
        private EventDispatcherInterface $eventDispatcher,
        private ConfigResolverInterface $configResolver,
    ) {}

    /**
     * Displays and validates the forgot password form.
     */
    public function __invoke(Request $request): Response
    {
        $form = $this->createForgotPasswordForm();
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render(
                $this->configResolver->getParameter('template.user.forgot_password', 'ngsite'),
                [
                    'form' => $form->createView(),
                ],
            );
        }

        /** @var \Ibexa\Contracts\Core\Repository\Values\User\User[] $users */
        $users = $this->userService->loadUsersByEmail($form->get('email')->getData());

        $passwordResetRequestEvent = new UserEvents\PasswordResetRequestEvent(
            $form->get('email')->getData(),
            $users[0] ?? null,
        );

        $passwordResetRequestEvent->setParameter('form', $form);

        $this->eventDispatcher->dispatch($passwordResetRequestEvent, SiteEvents::USER_PASSWORD_RESET_REQUEST);

        return $this->render(
            $this->configResolver->getParameter('template.user.forgot_password_sent', 'ngsite'),
        );
    }

    /**
     * Creates forgot password form.
     */
    private function createForgotPasswordForm(): FormInterface
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
