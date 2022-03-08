<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller\User;

use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Security\Authorization\Attribute;
use Netgen\Bundle\IbexaFormsBundle\Form\DataWrapper;
use Netgen\Bundle\IbexaFormsBundle\Form\Type\CreateUserType;
use Netgen\Bundle\SiteBundle\Controller\Controller;
use Netgen\Bundle\SiteBundle\Event\SiteEvents;
use Netgen\Bundle\SiteBundle\Event\User as UserEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class Register extends Controller
{
    protected UserService $userService;

    protected ContentTypeService $contentTypeService;

    protected EventDispatcherInterface $eventDispatcher;

    protected Repository $repository;

    protected ConfigResolverInterface $configResolver;

    public function __construct(
        UserService $userService,
        ContentTypeService $contentTypeService,
        EventDispatcherInterface $eventDispatcher,
        Repository $repository,
        ConfigResolverInterface $configResolver
    ) {
        $this->userService = $userService;
        $this->contentTypeService = $contentTypeService;
        $this->eventDispatcher = $eventDispatcher;
        $this->repository = $repository;
        $this->configResolver = $configResolver;
    }

    /**
     * Registers user on the site.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException if user does not have permission
     */
    public function __invoke(Request $request): Response
    {
        $this->denyAccessUnlessGranted(new Attribute('user', 'register'));

        $contentTypeIdentifier = $this->configResolver->getParameter('user.content_type_identifier', 'ngsite');
        $contentType = $this->contentTypeService->loadContentTypeByIdentifier($contentTypeIdentifier);
        $languages = $this->configResolver->getParameter('languages');
        $userCreateStruct = $this->userService->newUserCreateStruct(
            '',
            '',
            '',
            $languages[0],
            $contentType,
        );

        $userCreateStruct->enabled = (bool) $this->configResolver->getParameter('user.auto_enable', 'ngsite');
        $userCreateStruct->alwaysAvailable = (bool) $contentType->defaultAlwaysAvailable;

        $data = new DataWrapper($userCreateStruct, $userCreateStruct->contentType);

        $form = $this->createForm(
            CreateUserType::class,
            $data,
            [
                'translation_domain' => 'ngsite_user',
            ],
        );

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render(
                $this->configResolver->getParameter('template.user.register', 'ngsite'),
                [
                    'form' => $form->createView(),
                ],
            );
        }

        $users = $this->userService->loadUsersByEmail($form->getData()->payload->email);

        if (!empty($users)) {
            return $this->render(
                $this->configResolver->getParameter('template.user.register', 'ngsite'),
                [
                    'form' => $form->createView(),
                    'error' => 'email_in_use',
                ],
            );
        }

        try {
            $this->userService->loadUserByLogin($form->getData()->payload->login);

            return $this->render(
                $this->configResolver->getParameter('template.user.register', 'ngsite'),
                [
                    'form' => $form->createView(),
                    'error' => 'username_taken',
                ],
            );
        } catch (NotFoundException $e) {
            // do nothing
        }

        $userGroupId = $this->configResolver->getParameter('user.user_group_content_id', 'ngsite');

        $preUserRegisterEvent = new UserEvents\PreRegisterEvent($data->payload);
        $this->eventDispatcher->dispatch($preUserRegisterEvent, SiteEvents::USER_PRE_REGISTER);
        $data->payload = $preUserRegisterEvent->getUserCreateStruct();

        foreach ($data->payload->fields as $field) {
            if ($field->fieldTypeIdentifier !== 'ezuser') {
                continue;
            }

            $field->value->login = $data->payload->login;
            $field->value->email = $data->payload->email;
            $field->value->plainPassword = $data->payload->password;
            $field->value->enabled = $data->payload->enabled;

            break;
        }

        /** @var \Ibexa\Contracts\Core\Repository\Values\User\User $newUser */
        $newUser = $this->repository->sudo(
            static fn (Repository $repository): User => $repository->getUserService()->createUser(
                $data->payload,
                [$repository->getUserService()->loadUserGroup($userGroupId)],
            ),
        );

        $userRegisterEvent = new UserEvents\PostRegisterEvent($newUser);
        $this->eventDispatcher->dispatch($userRegisterEvent, SiteEvents::USER_POST_REGISTER);

        if ($newUser->enabled) {
            return $this->render(
                $this->configResolver->getParameter('template.user.register_success', 'ngsite'),
            );
        }

        if ($this->configResolver->getParameter('user.require_admin_activation', 'ngsite')) {
            return $this->render(
                $this->configResolver->getParameter('template.user.activate_admin_activation_pending', 'ngsite'),
            );
        }

        return $this->render(
            $this->configResolver->getParameter('template.user.activate_sent', 'ngsite'),
        );
    }
}
