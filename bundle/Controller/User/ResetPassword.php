<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller\User;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Values\User\User;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Netgen\Bundle\SiteBundle\Controller\Controller;
use Netgen\Bundle\SiteBundle\Entity\EzUserAccountKey;
use Netgen\Bundle\SiteBundle\Entity\Repository\EzUserAccountKeyRepository;
use Netgen\Bundle\SiteBundle\Event\SiteEvents;
use Netgen\Bundle\SiteBundle\Event\User as UserEvents;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function time;

class ResetPassword extends Controller
{
    /**
     * @var \eZ\Publish\API\Repository\UserService
     */
    protected $userService;

    /**
     * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var \Netgen\Bundle\SiteBundle\Entity\Repository\EzUserAccountKeyRepository
     */
    protected $accountKeyRepository;

    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    public function __construct(
        UserService $userService,
        EventDispatcherInterface $eventDispatcher,
        EzUserAccountKeyRepository $accountKeyRepository,
        Repository $repository,
        ConfigResolverInterface $configResolver
    ) {
        $this->userService = $userService;
        $this->eventDispatcher = $eventDispatcher;
        $this->accountKeyRepository = $accountKeyRepository;
        $this->repository = $repository;
        $this->configResolver = $configResolver;
    }

    /**
     * Displays and validates the reset password form.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If hash key does not exist
     */
    public function __invoke(Request $request, string $hash): Response
    {
        $accountKey = $this->accountKeyRepository->getByHash($hash);

        if (!$accountKey instanceof EzUserAccountKey) {
            throw new NotFoundHttpException();
        }

        if (time() - $accountKey->getTime() > $this->configResolver->getParameter('user.forgot_password_hash_validity_time', 'ngsite')) {
            $this->accountKeyRepository->removeByHash($hash);

            return $this->render(
                $this->configResolver->getParameter('template.user.reset_password_done', 'ngsite'),
                [
                    'error' => 'hash_expired',
                ]
            );
        }

        try {
            $user = $this->userService->loadUser($accountKey->getUserId());
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException();
        }

        $form = $this->createResetPasswordForm();
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render(
                $this->configResolver->getParameter('template.user.reset_password', 'ngsite'),
                [
                    'form' => $form->createView(),
                ]
            );
        }

        $data = $form->getData();

        $userUpdateStruct = $this->userService->newUserUpdateStruct();
        $userUpdateStruct->password = $data['password'];

        $prePasswordResetEvent = new UserEvents\PrePasswordResetEvent($user, $userUpdateStruct);
        $this->eventDispatcher->dispatch($prePasswordResetEvent, SiteEvents::USER_PRE_PASSWORD_RESET);
        $userUpdateStruct = $prePasswordResetEvent->getUserUpdateStruct();

        $user = $this->repository->sudo(
            static function (Repository $repository) use ($user, $userUpdateStruct): User {
                return $repository->getUserService()->updateUser($user, $userUpdateStruct);
            }
        );

        $postPasswordResetEvent = new UserEvents\PostPasswordResetEvent($user);
        $this->eventDispatcher->dispatch($postPasswordResetEvent, SiteEvents::USER_POST_PASSWORD_RESET);

        return $this->render(
            $this->configResolver->getParameter('template.user.reset_password_done', 'ngsite')
        );
    }

    /**
     * Creates reset password form.
     */
    protected function createResetPasswordForm(): FormInterface
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
