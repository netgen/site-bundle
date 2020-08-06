<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller\User;

use eZ\Publish\API\Repository\UserService;
use Netgen\Bundle\SiteBundle\Controller\Controller;
use Netgen\Bundle\SiteBundle\Event\SiteEvents;
use Netgen\Bundle\SiteBundle\Event\User as UserEvents;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ForgotPassword extends Controller
{
    /**
     * @var \eZ\Publish\API\Repository\UserService
     */
    protected $userService;

    /**
     * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(
        UserService $userService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->userService = $userService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Displays and validates the forgot password form.
     */
    public function __invoke(Request $request): Response
    {
        $form = $this->createForgotPasswordForm();
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render(
                $this->getConfigResolver()->getParameter('template.user.forgot_password', 'ngsite'),
                [
                    'form' => $form->createView(),
                ]
            );
        }

        $users = $this->userService->loadUsersByEmail($form->get('email')->getData());

        $passwordResetRequestEvent = new UserEvents\PasswordResetRequestEvent(
            $form->get('email')->getData(),
            $users[0] ?? null
        );

        $this->eventDispatcher->dispatch($passwordResetRequestEvent, SiteEvents::USER_PASSWORD_RESET_REQUEST);

        return $this->render(
            $this->getConfigResolver()->getParameter('template.user.forgot_password_sent', 'ngsite')
        );
    }

    /**
     * Creates forgot password form.
     */
    protected function createForgotPasswordForm(): FormInterface
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
                ]
            )->getForm();
    }
}
