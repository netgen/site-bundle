<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller\User;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Netgen\Bundle\SiteBundle\Controller\Controller;
use Netgen\Bundle\SiteBundle\Entity\Repository\UserAccountKeyRepository;
use Netgen\Bundle\SiteBundle\Entity\UserAccountKey;
use Netgen\Bundle\SiteBundle\Event\SiteEvents;
use Netgen\Bundle\SiteBundle\Event\User as UserEvents;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

use function time;

final class ResetPassword extends Controller
{
    public function __construct(
        private UserService $userService,
        private EventDispatcherInterface $eventDispatcher,
        private UserAccountKeyRepository $accountKeyRepository,
        private Repository $repository,
        private ConfigResolverInterface $configResolver,
    ) {
    }

    /**
     * Displays and validates the reset password form.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If hash key does not exist
     */
    public function __invoke(Request $request, string $hash): Response
    {
        $accountKey = $this->accountKeyRepository->getByHash($hash);

        if (!$accountKey instanceof UserAccountKey) {
            throw $this->createNotFoundException();
        }

        if (time() - $accountKey->getTime() > $this->configResolver->getParameter('user.forgot_password_hash_validity_time', 'ngsite')) {
            $this->accountKeyRepository->removeByHash($hash);

            return $this->render(
                $this->configResolver->getParameter('template.user.reset_password_done', 'ngsite'),
                [
                    'error' => 'hash_expired',
                ],
            );
        }

        try {
            $user = $this->userService->loadUser($accountKey->getUserId());
        } catch (NotFoundException) {
            throw $this->createNotFoundException();
        }

        $form = $this->createResetPasswordForm();
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render(
                $this->configResolver->getParameter('template.user.reset_password', 'ngsite'),
                [
                    'form' => $form->createView(),
                ],
            );
        }

        $data = $form->getData();

        $userUpdateStruct = $this->userService->newUserUpdateStruct();
        $userUpdateStruct->password = $data['password'];

        $prePasswordResetEvent = new UserEvents\PrePasswordResetEvent($user, $userUpdateStruct);
        $this->eventDispatcher->dispatch($prePasswordResetEvent, SiteEvents::USER_PRE_PASSWORD_RESET);
        $userUpdateStruct = $prePasswordResetEvent->getUserUpdateStruct();

        $user = $this->repository->sudo(
            fn (): User => $this->repository->getUserService()->updateUser($user, $userUpdateStruct),
        );

        $postPasswordResetEvent = new UserEvents\PostPasswordResetEvent($user);
        $this->eventDispatcher->dispatch($postPasswordResetEvent, SiteEvents::USER_POST_PASSWORD_RESET);

        return $this->render(
            $this->configResolver->getParameter('template.user.reset_password_done', 'ngsite'),
        );
    }

    /**
     * Creates reset password form.
     */
    private function createResetPasswordForm(): FormInterface
    {
        $passwordOptions = [
            'type' => PasswordType::class,
            'required' => true,
            'options' => [
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
            ],
        ];

        return $this->createFormBuilder(null, ['translation_domain' => 'ngsite_user'])
            ->add('password', RepeatedType::class, $passwordOptions)
            ->getForm();
    }
}
