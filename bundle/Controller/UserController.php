<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Values\User\User;
use Netgen\Bundle\EzFormsBundle\Form\DataWrapper;
use Netgen\Bundle\EzFormsBundle\Form\Type\CreateUserType;
use Netgen\Bundle\SiteBundle\Entity\EzUserAccountKey;
use Netgen\Bundle\SiteBundle\Entity\Repository\EzUserAccountKeyRepository;
use Netgen\Bundle\SiteBundle\Event\SiteEvents;
use Netgen\Bundle\SiteBundle\Event\User as UserEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints;

use function time;

class UserController extends Controller
{
    protected UserService $userService;

    protected EventDispatcherInterface $eventDispatcher;

    protected FormFactoryInterface $formFactory;

    protected EzUserAccountKeyRepository $accountKeyRepository;

    public function __construct(
        UserService $userService,
        EventDispatcherInterface $eventDispatcher,
        FormFactoryInterface $formFactory,
        EzUserAccountKeyRepository $accountKeyRepository
    ) {
        $this->userService = $userService;
        $this->eventDispatcher = $eventDispatcher;
        $this->formFactory = $formFactory;
        $this->accountKeyRepository = $accountKeyRepository;
    }

    /**
     * Registers user on the site.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException if user does not have permission
     */
    public function register(Request $request): Response
    {
        $contentTypeIdentifier = $this->getConfigResolver()->getParameter('user.content_type_identifier', 'ngsite');
        $contentType = $this->getRepository()->getContentTypeService()->loadContentTypeByIdentifier($contentTypeIdentifier);
        $languages = $this->getConfigResolver()->getParameter('languages');
        $userCreateStruct = $this->userService->newUserCreateStruct(
            null,
            null,
            null,
            $languages[0],
            $contentType,
        );

        $userCreateStruct->enabled = (bool) $this->getConfigResolver()->getParameter('user.auto_enable', 'ngsite');
        $userCreateStruct->alwaysAvailable = (bool) $contentType->defaultAlwaysAvailable;

        $data = new DataWrapper($userCreateStruct, $userCreateStruct->contentType);

        $formBuilder = $this->formFactory->createBuilder(
            CreateUserType::class,
            $data,
            [
                'translation_domain' => 'ngsite_user',
            ],
        );

        $form = $formBuilder->getForm();
        $form->handleRequest($request);

        if (!$form->isValid()) {
            return $this->render(
                $this->getConfigResolver()->getParameter('template.user.register', 'ngsite'),
                [
                    'form' => $form->createView(),
                ],
            );
        }

        $users = $this->userService->loadUsersByEmail($form->getData()->payload->email);

        if (!empty($users)) {
            return $this->render(
                $this->getConfigResolver()->getParameter('template.user.register', 'ngsite'),
                [
                    'form' => $form->createView(),
                    'error' => 'email_in_use',
                ],
            );
        }

        try {
            $this->userService->loadUserByLogin($form->getData()->payload->login);

            return $this->render(
                $this->getConfigResolver()->getParameter('template.user.register', 'ngsite'),
                [
                    'form' => $form->createView(),
                    'error' => 'username_taken',
                ],
            );
        } catch (NotFoundException $e) {
            // do nothing
        }

        $userGroupId = $this->getConfigResolver()->getParameter('user.user_group_content_id', 'ngsite');

        $preUserRegisterEvent = new UserEvents\PreRegisterEvent($data->payload);
        $this->eventDispatcher->dispatch(SiteEvents::USER_PRE_REGISTER, $preUserRegisterEvent);
        $data->payload = $preUserRegisterEvent->getUserCreateStruct();

        /** @var \eZ\Publish\API\Repository\Values\User\User $newUser */
        $newUser = $this->getRepository()->sudo(
            static function (Repository $repository) use ($data, $userGroupId): User {
                $userGroup = $repository->getUserService()->loadUserGroup($userGroupId);

                return $repository->getUserService()->createUser(
                    $data->payload,
                    [$userGroup],
                );
            },
        );

        $userRegisterEvent = new UserEvents\PostRegisterEvent($newUser);
        $this->eventDispatcher->dispatch(SiteEvents::USER_POST_REGISTER, $userRegisterEvent);

        if ($newUser->enabled) {
            return $this->render(
                $this->getConfigResolver()->getParameter('template.user.register_success', 'ngsite'),
            );
        }

        if ($this->getConfigResolver()->getParameter('user.require_admin_activation', 'ngsite')) {
            return $this->render(
                $this->getConfigResolver()->getParameter('template.user.activate_admin_activation_pending', 'ngsite'),
            );
        }

        return $this->render(
            $this->getConfigResolver()->getParameter('template.user.activate_sent', 'ngsite'),
        );
    }

    /**
     * Displays and validates the form for sending an activation mail.
     */
    public function activationForm(Request $request): Response
    {
        $form = $this->createActivationForm();
        $form->handleRequest($request);

        if (!$form->isValid()) {
            return $this->render(
                $this->getConfigResolver()->getParameter('template.user.activate', 'ngsite'),
                [
                    'form' => $form->createView(),
                ],
            );
        }

        $users = $this->userService->loadUsersByEmail($form->get('email')->getData());

        $activationRequestEvent = new UserEvents\ActivationRequestEvent(
            $form->get('email')->getData(),
            $users[0] ?? null,
        );

        $this->eventDispatcher->dispatch(SiteEvents::USER_ACTIVATION_REQUEST, $activationRequestEvent);

        return $this->render(
            $this->getConfigResolver()->getParameter('template.user.activate_sent', 'ngsite'),
        );
    }

    /**
     * Activates the user by hash key.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If hash key does not exist
     */
    public function activate(string $hash): Response
    {
        $accountKey = $this->accountKeyRepository->getByHash($hash);

        if (!$accountKey instanceof EzUserAccountKey) {
            throw $this->createNotFoundException();
        }

        if (time() - $accountKey->getTime() > $this->getConfigResolver()->getParameter('user.activate_hash_validity_time', 'ngsite')) {
            $this->accountKeyRepository->removeByHash($hash);

            return $this->render(
                $this->getConfigResolver()->getParameter('template.user.activate_done', 'ngsite'),
                [
                    'error' => 'hash_expired',
                ],
            );
        }

        try {
            $user = $this->userService->loadUser($accountKey->getUserId());
        } catch (NotFoundException $e) {
            throw $this->createNotFoundException();
        }

        $userUpdateStruct = $this->userService->newUserUpdateStruct();
        $userUpdateStruct->enabled = true;

        $preActivateEvent = new UserEvents\PreActivateEvent($user, $userUpdateStruct);
        $this->eventDispatcher->dispatch(SiteEvents::USER_PRE_ACTIVATE, $preActivateEvent);
        $userUpdateStruct = $preActivateEvent->getUserUpdateStruct();

        $user = $this->getRepository()->sudo(
            static fn (Repository $repository): User => $repository->getUserService()->updateUser($user, $userUpdateStruct),
        );

        $postActivateEvent = new UserEvents\PostActivateEvent($user);
        $this->eventDispatcher->dispatch(SiteEvents::USER_POST_ACTIVATE, $postActivateEvent);

        return $this->render(
            $this->getConfigResolver()->getParameter('template.user.activate_done', 'ngsite'),
        );
    }

    /**
     * Displays and validates the forgot password form.
     */
    public function forgotPassword(Request $request): Response
    {
        $form = $this->createForgotPasswordForm();
        $form->handleRequest($request);

        if (!$form->isValid()) {
            return $this->render(
                $this->getConfigResolver()->getParameter('template.user.forgot_password', 'ngsite'),
                [
                    'form' => $form->createView(),
                ],
            );
        }

        $users = $this->userService->loadUsersByEmail($form->get('email')->getData());

        $passwordResetRequestEvent = new UserEvents\PasswordResetRequestEvent(
            $form->get('email')->getData(),
            $users[0] ?? null,
        );

        $this->eventDispatcher->dispatch(SiteEvents::USER_PASSWORD_RESET_REQUEST, $passwordResetRequestEvent);

        return $this->render(
            $this->getConfigResolver()->getParameter('template.user.forgot_password_sent', 'ngsite'),
        );
    }

    /**
     * Displays and validates the reset password form.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If hash key does not exist
     */
    public function resetPassword(Request $request, string $hash): Response
    {
        $accountKey = $this->accountKeyRepository->getByHash($hash);

        if (!$accountKey instanceof EzUserAccountKey) {
            throw $this->createNotFoundException();
        }

        if (time() - $accountKey->getTime() > $this->getConfigResolver()->getParameter('user.forgot_password_hash_validity_time', 'ngsite')) {
            $this->accountKeyRepository->removeByHash($hash);

            return $this->render(
                $this->getConfigResolver()->getParameter('template.user.reset_password_done', 'ngsite'),
                [
                    'error' => 'hash_expired',
                ],
            );
        }

        try {
            $user = $this->userService->loadUser($accountKey->getUserId());
        } catch (NotFoundException $e) {
            throw $this->createNotFoundException();
        }

        $form = $this->createResetPasswordForm();
        $form->handleRequest($request);

        if (!$form->isValid()) {
            return $this->render(
                $this->getConfigResolver()->getParameter('template.user.reset_password', 'ngsite'),
                [
                    'form' => $form->createView(),
                ],
            );
        }

        $data = $form->getData();

        $userUpdateStruct = $this->userService->newUserUpdateStruct();
        $userUpdateStruct->password = $data['password'];

        $prePasswordResetEvent = new UserEvents\PrePasswordResetEvent($user, $userUpdateStruct);
        $this->eventDispatcher->dispatch(SiteEvents::USER_PRE_PASSWORD_RESET, $prePasswordResetEvent);
        $userUpdateStruct = $prePasswordResetEvent->getUserUpdateStruct();

        $user = $this->getRepository()->sudo(
            static fn (Repository $repository): User => $repository->getUserService()->updateUser($user, $userUpdateStruct),
        );

        $postPasswordResetEvent = new UserEvents\PostPasswordResetEvent($user);
        $this->eventDispatcher->dispatch(SiteEvents::USER_POST_PASSWORD_RESET, $postPasswordResetEvent);

        return $this->render(
            $this->getConfigResolver()->getParameter('template.user.reset_password_done', 'ngsite'),
        );
    }

    /**
     * Creates activation form.
     */
    protected function createActivationForm(): FormInterface
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
                ],
            )->getForm();
    }

    /**
     * Creates reset password form.
     */
    protected function createResetPasswordForm(): FormInterface
    {
        $minLength = (int) $this->container->getParameter('netgen.ezforms.form.type.fieldtype.ezuser.parameters.min_password_length');

        $passwordConstraints = [
            new Constraints\NotBlank(),
        ];

        if ($minLength > 0) {
            $passwordConstraints[] = new Constraints\Length(
                [
                    'min' => $minLength,
                ],
            );
        }

        $passwordOptions = [
            'type' => PasswordType::class,
            'required' => true,
            'options' => [
                'constraints' => $passwordConstraints,
            ],
        ];

        return $this->createFormBuilder(null, ['translation_domain' => 'ngsite_user'])
            ->add('password', RepeatedType::class, $passwordOptions)
            ->getForm();
    }
}
