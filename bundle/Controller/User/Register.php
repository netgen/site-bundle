<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller\User;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Values\User\User;
use eZ\Publish\Core\MVC\Symfony\Security\Authorization\Attribute;
use Netgen\Bundle\EzFormsBundle\Form\DataWrapper;
use Netgen\Bundle\EzFormsBundle\Form\Type\CreateUserType;
use Netgen\Bundle\SiteBundle\Controller\Controller;
use Netgen\Bundle\SiteBundle\Event\SiteEvents;
use Netgen\Bundle\SiteBundle\Event\User as UserEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class Register extends Controller
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
     * @var \Symfony\Component\Form\FormFactoryInterface
     */
    protected $formFactory;

    public function __construct(
        UserService $userService,
        EventDispatcherInterface $eventDispatcher,
        FormFactoryInterface $formFactory
    ) {
        $this->userService = $userService;
        $this->eventDispatcher = $eventDispatcher;
        $this->formFactory = $formFactory;
    }

    /**
     * Registers user on the site.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException if user does not have permission
     */
    public function __invoke(Request $request): Response
    {
        $this->denyAccessUnlessGranted(new Attribute('user', 'register'));

        $contentTypeIdentifier = $this->getConfigResolver()->getParameter('user.content_type_identifier', 'ngsite');
        $contentType = $this->getRepository()->getContentTypeService()->loadContentTypeByIdentifier($contentTypeIdentifier);
        $languages = $this->getConfigResolver()->getParameter('languages');
        $userCreateStruct = $this->userService->newUserCreateStruct(
            '',
            '',
            '',
            $languages[0],
            $contentType
        );

        $userCreateStruct->enabled = (bool) $this->getConfigResolver()->getParameter('user.auto_enable', 'ngsite');
        $userCreateStruct->alwaysAvailable = (bool) $contentType->defaultAlwaysAvailable;

        $data = new DataWrapper($userCreateStruct, $userCreateStruct->contentType);

        $formBuilder = $this->formFactory->createBuilder(
            CreateUserType::class,
            $data,
            [
                'translation_domain' => 'ngsite_user',
            ]
        );

        $form = $formBuilder->getForm();
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render(
                $this->getConfigResolver()->getParameter('template.user.register', 'ngsite'),
                [
                    'form' => $form->createView(),
                ]
            );
        }

        $users = $this->userService->loadUsersByEmail($form->getData()->payload->email);

        if (!empty($users)) {
            return $this->render(
                $this->getConfigResolver()->getParameter('template.user.register', 'ngsite'),
                [
                    'form' => $form->createView(),
                    'error' => 'email_in_use',
                ]
            );
        }

        try {
            $this->userService->loadUserByLogin($form->getData()->payload->login);

            return $this->render(
                $this->getConfigResolver()->getParameter('template.user.register', 'ngsite'),
                [
                    'form' => $form->createView(),
                    'error' => 'username_taken',
                ]
            );
        } catch (NotFoundException $e) {
            // do nothing
        }

        $userGroupId = $this->getConfigResolver()->getParameter('user.user_group_content_id', 'ngsite');

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

        /** @var \eZ\Publish\API\Repository\Values\User\User $newUser */
        $newUser = $this->getRepository()->sudo(
            static function (Repository $repository) use ($data, $userGroupId): User {
                $userGroup = $repository->getUserService()->loadUserGroup($userGroupId);

                return $repository->getUserService()->createUser(
                    $data->payload,
                    [$userGroup]
                );
            }
        );

        $userRegisterEvent = new UserEvents\PostRegisterEvent($newUser);
        $this->eventDispatcher->dispatch($userRegisterEvent, SiteEvents::USER_POST_REGISTER);

        if ($newUser->enabled) {
            return $this->render(
                $this->getConfigResolver()->getParameter('template.user.register_success', 'ngsite')
            );
        }

        if ($this->getConfigResolver()->getParameter('user.require_admin_activation', 'ngsite')) {
            return $this->render(
                $this->getConfigResolver()->getParameter('template.user.activate_admin_activation_pending', 'ngsite')
            );
        }

        return $this->render(
            $this->getConfigResolver()->getParameter('template.user.activate_sent', 'ngsite')
        );
    }
}
