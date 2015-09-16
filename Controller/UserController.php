<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use Netgen\Bundle\MoreBundle\Entity\EzUserAccountKey;
use Netgen\Bundle\MoreBundle\Event\User\ActivationRequestEvent;
use Netgen\Bundle\MoreBundle\Event\User\PasswordResetRequestEvent;
use Netgen\Bundle\MoreBundle\Event\User\PostPasswordResetEvent;
use Netgen\Bundle\MoreBundle\Event\User\PreActivateEvent;
use Netgen\Bundle\MoreBundle\Event\User\PostActivateEvent;
use Netgen\Bundle\MoreBundle\Event\User\PostRegisterEvent;
use Netgen\Bundle\MoreBundle\Event\User\PrePasswordResetEvent;
use Netgen\Bundle\MoreBundle\Event\User\PreRegisterEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints;
use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\API\Repository\Values\User\User;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use Netgen\Bundle\EzFormsBundle\Form\DataWrapper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Netgen\Bundle\MoreBundle\Event\MVCEvents;

class UserController extends Controller
{
    /**
     * @var \eZ\Publish\API\Repository\UserService
     */
    protected $userService;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * Constructor
     *
     * @param \eZ\Publish\API\Repository\UserService $userService
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    public function __construct( UserService $userService, EventDispatcherInterface $eventDispatcher )
    {
        $this->userService = $userService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Registers user on the site
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException if user does not have permission
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function register( Request $request )
    {
        $autoEnable = (bool)$this->getConfigResolver()->getParameter( 'user.auto_enable', 'ngmore' );

        $contentTypeIdentifier = $this->getConfigResolver()->getParameter( 'user.content_type_identifier', 'ngmore' );
        $contentType = $this->getRepository()->getContentTypeService()->loadContentTypeByIdentifier( $contentTypeIdentifier );
        $languages = $this->getConfigResolver()->getParameter( "languages" );
        $userCreateStruct = $this->userService->newUserCreateStruct(
            null,
            null,
            null,
            $languages[0],
            $contentType
        );

        $userCreateStruct->enabled = $autoEnable;

        $data = new DataWrapper( $userCreateStruct, $userCreateStruct->contentType );

        $formBuilder = $this->container->get( "form.factory" )->createBuilder(
            "ezforms_create_user",
            $data,
            array(
                "translation_domain" => "ngmore_user"
            )
        );

        $form = $formBuilder->getForm();
        $form->handleRequest( $request );

        if ( $form->isValid() )
        {
            $users = $this->userService->loadUsersByEmail( $form->getData()->payload->email );

            if ( count( $users ) > 0 )
            {
                return $this->render(
                    $this->getConfigResolver()->getParameter( "template.user.register", "ngmore" ),
                    array(
                        "form" => $form->createView(),
                        "error" => 'email_in_use'
                    )
                );
            }

            try
            {
                $this->userService->loadUserByLogin( $form->getData()->payload->login );

                return $this->render(
                    $this->getConfigResolver()->getParameter( "template.user.register", "ngmore" ),
                    array(
                        "form" => $form->createView(),
                        "error" => 'username_taken'
                    )
                );
            }
            catch ( NotFoundException $e )
            {
                // do nothing
            }

            $userGroupId = $this->getConfigResolver()->getParameter( 'user.user_group_content_id', 'ngmore' );

            $preUserRegisterEvent = new PreRegisterEvent( $data->payload );
            $this->eventDispatcher->dispatch( MVCEvents::USER_PRE_REGISTER, $preUserRegisterEvent );

            if ( $data->payload !== $preUserRegisterEvent->getUserCreateStruct() )
            {
                $data->payload = $preUserRegisterEvent->getUserCreateStruct();
            }

            // @TODO: There is a known issue in eZ Publish kernel where signal slot repository
            // is NOT used in sudo calls, preventing the "auto enable" functionality from working
            // See: https://github.com/ezsystems/ezpublish-kernel/pull/1393
            $newUser = $this->getRepository()->sudo(
                function( Repository $repository ) use ( $data, $userGroupId )
                {
                    $userGroup = $repository->getUserService()->loadUserGroup( $userGroupId );

                    return $repository->getUserService()->createUser(
                        $data->payload,
                        array( $userGroup )
                    );
                }
            );

            $userRegisterEvent = new PostRegisterEvent( $newUser, $autoEnable );
            $this->eventDispatcher->dispatch( MVCEvents::USER_POST_REGISTER, $userRegisterEvent );

            if ( $autoEnable )
            {
                return $this->render(
                    $this->getConfigResolver()->getParameter( 'template.user.register_success', 'ngmore' )
                );
            }

            return $this->render(
                $this->getConfigResolver()->getParameter( 'template.user.activate_sent', 'ngmore' )
            );
        }

        return $this->render(
            $this->getConfigResolver()->getParameter( "template.user.register", "ngmore" ),
            array(
                "form" => $form->createView()
            )
        );
    }

    /**
     * Displays and validates the form for sending an activation mail
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function activationForm( Request $request )
    {
        $form = $this->createActivationForm();
        $form->handleRequest( $request );

        if ( !$form->isValid() )
        {
            return $this->render(
                $this->getConfigResolver()->getParameter( 'template.user.activate', 'ngmore' ),
                array(
                    'form' => $form->createView()
                )
            );
        }

        $email = $form->get( 'email' )->getData();
        $users = $this->userService->loadUsersByEmail( $form->get( 'email' )->getData() );

        if ( empty( $users ) )
        {
            $user = null;
        }
        else
        {
            $user = $users[0];
        }

        $activationRequestEvent = new ActivationRequestEvent( $user, $email );
        $this->eventDispatcher->dispatch( MVCEvents::USER_ACTIVATION_REQUEST, $activationRequestEvent );


        return $this->render(
            $this->getConfigResolver()->getParameter( 'template.user.activate_sent', 'ngmore' )
        );
    }

    /**
     * Activates the user by hash key
     *
     * @param string $hash
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If hash key does not exist
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function activate( $hash )
    {
        /** @var \Netgen\Bundle\MoreBundle\Entity\Repository\EzUserAccountKeyRepository $ezUserAccountKeyRepository */
        $ezUserAccountKeyRepository  = $this->get( 'ngmore.repository.ezuser_accountkey' );

        $accountKey = $ezUserAccountKeyRepository->getByHash( $hash );

        if ( !$accountKey instanceof EzUserAccountKey )
        {
            throw new NotFoundHttpException();
        }

        if ( time() - $accountKey->getTime() > $this->getConfigResolver()->getParameter( 'user.activate_hash_validity_time', 'ngmore' ) )
        {
            $ezUserAccountKeyRepository->removeByHash( $hash );

            return $this->render(
                $this->getConfigResolver()->getParameter( "template.user.activate_done", "ngmore" ),
                array(
                    'error' => 'hash_expired'
                )
            );
        }

        try
        {
            $user = $this->userService->loadUser( $accountKey->getUserId() );
        }
        catch ( NotFoundException $e )
        {
            throw new NotFoundHttpException();
        }

        $preActivateEvent = new PreActivateEvent( $user );
        $this->eventDispatcher->dispatch( MVCEvents::USER_PRE_ACTIVATE, $preActivateEvent );

        if ( $user !== $preActivateEvent->getUser() )
        {
            $user = $preActivateEvent->getUser();
        }

        $user = $this->enableUser( $user );

        $postActivateEvent = new PostActivateEvent( $user );
        $this->eventDispatcher->dispatch( MVCEvents::USER_POST_ACTIVATE, $postActivateEvent );

        return $this->render(
            $this->getConfigResolver()->getParameter( "template.user.activate_done", "ngmore" )
        );
    }

    /**
     * Displays and validates the forgot password form
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function forgotPassword( Request $request )
    {
        $form = $this->createForgotPasswordForm();
        $form->handleRequest( $request );

        if ( !$form->isValid() )
        {
            return $this->render(
                $this->getConfigResolver()->getParameter( 'template.user.forgot_password', 'ngmore' ),
                array(
                    'form' => $form->createView()
                )
            );
        }

        $users = $this->userService->loadUsersByEmail( $form->get( 'email' )->getData() );
        $email = $form->get( 'email' )->getData();

        if ( empty( $users ) )
        {
            $passwordResetRequestEvent = new PasswordResetRequestEvent( $email );
        }
        else
        {
            $passwordResetRequestEvent = new PasswordResetRequestEvent( $email, $users[0] );
        }

        $this->eventDispatcher->dispatch( MVCEvents::USER_PASSWORD_RESET_REQUEST, $passwordResetRequestEvent );

        return $this->render(
            $this->getConfigResolver()->getParameter( 'template.user.forgot_password_sent', 'ngmore' )
        );
    }

    /**
     * Displays and validates the reset password form
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $hash
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If hash key does not exist
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resetPassword( Request $request, $hash )
    {
        /** @var \Netgen\Bundle\MoreBundle\Entity\Repository\EzUserAccountKeyRepository $ezUserAccountKeyRepository */
        $ezUserAccountKeyRepository = $this->get( 'ngmore.repository.ezuser_accountkey' );

        $accountKey = $ezUserAccountKeyRepository->getByHash( $hash );

        if ( !$accountKey instanceof EzUserAccountKey )
        {
            throw new NotFoundHttpException();
        }

        if ( time() - $accountKey->getTime() > $this->getConfigResolver()->getParameter( 'user.forgot_password_hash_validity_time', 'ngmore' ) )
        {
            $ezUserAccountKeyRepository->removeByHash( $hash );

            return $this->render(
                $this->getConfigResolver()->getParameter( "template.user.reset_password_done", "ngmore" ),
                array(
                    'error' => 'hash_expired'
                )
            );
        }

        try
        {
            $user = $this->userService->loadUser( $accountKey->getUserId() );
        }
        catch ( NotFoundException $e )
        {
            throw new NotFoundHttpException();
        }

        $form = $this->createResetPasswordForm();
        $form->handleRequest( $request );

        if ( !$form->isValid() )
        {
            return $this->render(
                $this->getConfigResolver()->getParameter( "template.user.reset_password", "ngmore" ),
                array(
                    'form' => $form->createView()
                )
            );
        }

        $data = $form->getData();

        $userUpdateStruct = $this->userService->newUserUpdateStruct();
        $userUpdateStruct->password = $data["password"];

        $prePasswordResetEvent = new PrePasswordResetEvent( $userUpdateStruct );
        $this->eventDispatcher->dispatch( MVCEvents::USER_PRE_PASSWORD_RESET, $prePasswordResetEvent );

        if ( $userUpdateStruct !== $prePasswordResetEvent->getUserUpdateStruct() )
        {
            $userUpdateStruct = $prePasswordResetEvent->getUserUpdateStruct();
        }

        $user = $this->getRepository()->sudo(
            function( Repository $repository ) use ( $user, $userUpdateStruct )
            {
                return $repository->getUserService()->updateUser( $user, $userUpdateStruct );
            }
        );

        $postPasswordResetEvent = new PostPasswordResetEvent( $user );
        $this->eventDispatcher->dispatch( MVCEvents::USER_POST_PASSWORD_RESET, $postPasswordResetEvent );

        return $this->render(
            $this->getConfigResolver()->getParameter( "template.user.reset_password_done", "ngmore" )
        );
    }

    /**
     * Creates activation form
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function createActivationForm()
    {
        return $this->createFormBuilder( null, array( "translation_domain" => "ngmore_user" ) )
            ->add(
                'email',
                'email',
                array(
                    'constraints' => array(
                        new Constraints\Email(),
                        new Constraints\NotBlank()
                    )
                )
            )->getForm();
    }

    /**
     * Creates forgot password form
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function createForgotPasswordForm()
    {
        return $this->createFormBuilder( null, array( "translation_domain" => "ngmore_user" ) )
            ->add(
                'email',
                'email',
                array(
                    'constraints' => array(
                        new Constraints\Email(),
                        new Constraints\NotBlank()
                    )
                )
            )->getForm();
    }

    /**
     * Creates reset password form
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function createResetPasswordForm()
    {
        $minLength = (int)$this->container->getParameter( "netgen.ezforms.form.type.fieldtype.ezuser.parameters.min_password_length" );

        $passwordConstraints = array(
            new Constraints\NotBlank()
        );

        if ( $minLength > 0 )
        {
            $passwordConstraints[] = new Constraints\Length(
                array(
                    "min" => $minLength,
                )
            );
        }

        $passwordOptions = array(
            "type" => "password",
            "required" => true,
            "options" => array(
                "constraints" => $passwordConstraints,
            )
        );

        return $this->createFormBuilder( null, array( "translation_domain" => "ngmore_user" ) )
            ->add( 'password', 'repeated', $passwordOptions )
            ->getForm();
    }

    /**
     * Enables the user
     *
     * @param \eZ\Publish\API\Repository\Values\User\User $user
     *
     * @return \eZ\Publish\API\Repository\Values\User\User
     */
    protected function enableUser( User $user )
    {
        $userUpdateStruct = $this->userService->newUserUpdateStruct();
        $userUpdateStruct->enabled = true;

        $user = $this->getRepository()->sudo(
            function( Repository $repository ) use ( $user, $userUpdateStruct )
            {
                return $repository->getUserService()->updateUser( $user, $userUpdateStruct );
            }
        );

        return $user;
    }
}
