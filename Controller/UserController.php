<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use Netgen\Bundle\MoreBundle\Entity\EzUserAccountKey;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints;
use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\API\Repository\Values\User\User;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use Netgen\Bundle\EzFormsBundle\Form\DataWrapper;
use Netgen\Bundle\MoreBundle\Helper\MailHelper;

class UserController extends Controller
{
    /**
     * @var \eZ\Publish\API\Repository\UserService
     */
    protected $userService;

    /**
     * @var \Netgen\Bundle\MoreBundle\Helper\MailHelper
     */
    protected $mailHelper;

    /**
     * @var bool
     */
    protected $autoEnable = false;

    /**
     * Constructor
     *
     * @param \eZ\Publish\API\Repository\UserService $userService
     * @param \Netgen\Bundle\MoreBundle\Helper\MailHelper $mailHelper
     */
    public function __construct(
        UserService $userService,
        MailHelper $mailHelper
    )
    {
        $this->userService = $userService;
        $this->mailHelper = $mailHelper;

        if ( $this->getConfigResolver()->hasParameter( 'user.auto_enable', 'ngmore' ) )
        {
            $this->autoEnable = $this->getConfigResolver()->getParameter( 'user.auto_enable', 'ngmore' );
        }
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
        if ( $this->getRepository()->hasAccess( 'user', 'register' ) !== true )
        {
            throw new AccessDeniedHttpException();
        }

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

        $userCreateStruct->enabled = $this->autoEnable;

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

            if ( $this->autoEnable )
            {
                $this->mailHelper
                    ->sendMail(
                        $newUser->email,
                        $this->getConfigResolver()->getParameter( 'template.user.mail.welcome', 'ngmore' ),
                        'ngmore.user.register.mail.subject',
                        array(
                            'user' => $newUser
                        )
                    );

                return $this->render(
                    $this->getConfigResolver()->getParameter( 'template.user.register_success', 'ngmore' )
                );
            }

            $accountKey = $this->getDoctrine()
                ->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )
                ->create( $newUser->id );

            $this->mailHelper
                ->sendMail(
                    $newUser->email,
                    $this->getConfigResolver()->getParameter( 'template.user.mail.activation', 'ngmore' ),
                    'ngmore.user.activate.mail.subject',
                    array(
                        'user' => $newUser,
                        'hash' => $accountKey->getHash()
                    )
                );

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
    public function resendActivationMail( Request $request )
    {
        $form = $this->createActivationForm();
        $form->handleRequest( $request );

        if ( $form->isValid() )
        {
            $users = $this->userService->loadUsersByEmail( $form->get( 'email' )->getData() );

            if ( empty( $users ) )
            {
                $this->mailHelper->sendMail(
                    $form->get( 'email' )->getData(),
                    $this->getConfigResolver()->getParameter( 'template.user.mail.activation_not_registered', 'ngmore' ),
                    'ngmore.user.activate.mail.activation_mail_not_registered.subject'
                );

                return $this->render(
                    $this->getConfigResolver()->getParameter( 'template.user.activate_sent', 'ngmore' )
                );
            }

            if ( $users[0]->enabled )
            {
                $this->mailHelper->sendMail(
                    $form->get( 'email' )->getData(),
                    $this->getConfigResolver()->getParameter( 'template.user.mail.activation_already_active', 'ngmore' ),
                    'ngmore.user.activate.mail.user_already_active.subject',
                    array(
                        'user' => $users[0]
                    )
                );
            }
            else if ( $this->getDoctrine()->getRepository( 'NetgenMoreBundle:NgUserSetting' )->isUserActivated( $users[0]->id ) )
            {
                $this->mailHelper->sendMail(
                    $form->get( 'email' )->getData(),
                    $this->getConfigResolver()->getParameter( 'template.user.mail.activation_disabled', 'ngmore' ),
                    'ngmore.user.activate.mail.user_disabled.subject',
                    array(
                        'user' => $users[0]
                    )
                );
            }
            else
            {
                $accountKey = $this->getDoctrine()
                    ->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )
                    ->create( $users[0]->id );

                $this->mailHelper
                    ->sendMail(
                        $users[0]->email,
                        $this->getConfigResolver()->getParameter( 'template.user.mail.activation', 'ngmore' ),
                        'ngmore.user.activate.mail.subject',
                        array(
                            'user' => $users[0],
                            'hash' => $accountKey->getHash()
                        )
                    );
            }

            return $this->render(
                $this->getConfigResolver()->getParameter( 'template.user.activate_sent', 'ngmore' )
            );
        }

        return $this->render(
            $this->getConfigResolver()->getParameter( 'template.user.activate', 'ngmore' ),
            array(
                'form' => $form->createView()
            )
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
    public function activateUser( $hash )
    {
        $accountKey = $this->getDoctrine()
            ->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )
            ->getByHash( $hash );

        if ( !$accountKey instanceof EzUserAccountKey )
        {
            throw new NotFoundHttpException();
        }

        if ( time() - $accountKey->getTime() > $this->getConfigResolver()->getParameter( 'user.activate_hash_validity_time', 'ngmore' ) )
        {
            $this->getDoctrine()
                ->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )
                ->removeByHash( $hash );

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

        if ( $user->enabled )
        {
            return $this->render(
                $this->getConfigResolver()->getParameter( "template.user.activate_done", "ngmore" ),
                array(
                    'error' => 'already_active'
                )
            );
        }

        $this->enableUser( $user );

        $this->mailHelper
            ->sendMail(
                $user->email,
                $this->getConfigResolver()->getParameter( 'template.user.mail.welcome', 'ngmore' ),
                'ngmore.user.register.mail.subject',
                array(
                    'user' => $user
                )
            );

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

        if ( $form->isValid() )
        {
            $users = $this->userService->loadUsersByEmail( $form->get( 'email' )->getData() );
            if ( empty( $users ) )
            {
                $this->mailHelper
                    ->sendMail(
                        $form->get( 'email' )->getData(),
                        $this->getConfigResolver()->getParameter( 'template.user.mail.forgot_password_not_registered', 'ngmore' ),
                        'ngmore.user.forgot_password.mail.email_not_registered.subject'
                    );
            }
            else if ( !$users[0]->enabled )
            {
                if ( $this->getDoctrine()->getRepository( 'NetgenMoreBundle:NgUserSetting' )->isUserActivated( $users[0]->id ) )
                {
                    $this->mailHelper
                        ->sendMail(
                            $form->get( 'email' )->getData(),
                            $this->getConfigResolver()->getParameter( 'template.user.mail.forgot_password_disabled', 'ngmore' ),
                            'ngmore.user.forgot_password.mail.user_disabled.subject',
                            array(
                                'user' => $users[0],
                            )
                        );
                }
                else
                {
                    $this->mailHelper
                        ->sendMail(
                            $form->get( 'email' )->getData(),
                            $this->getConfigResolver()->getParameter( 'template.user.mail.forgot_password_not_activated', 'ngmore' ),
                            'ngmore.user.forgot_password.mail.user_not_activated.subject',
                            array(
                                'user' => $users[0],
                            )
                        );
                }
            }
            else
            {
                $accountKey = $this->getDoctrine()
                    ->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )
                    ->create( $users[0]->id );

                $this->mailHelper
                    ->sendMail(
                        $users[0]->email,
                        $this->getConfigResolver()->getParameter( 'template.user.mail.forgot_password', 'ngmore' ),
                        'ngmore.user.forgot_password.mail.change_requested.subject',
                        array(
                            'user' => $users[0],
                            'hash' => $accountKey->getHash()
                        )
                    );
            }

            return $this->render(
                $this->getConfigResolver()->getParameter( 'template.user.forgot_password_sent', 'ngmore' )
            );
        }

        return $this->render(
            $this->getConfigResolver()->getParameter( 'template.user.forgot_password', 'ngmore' ),
            array(
                'form' => $form->createView()
            )
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
        $accountKey = $this->getDoctrine()
            ->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )
            ->getByHash( $hash );

        if ( !$accountKey instanceof EzUserAccountKey )
        {
            throw new NotFoundHttpException();
        }

        if ( time() - $accountKey->getTime() > $this->getConfigResolver()->getParameter( 'user.forgot_password_hash_validity_time', 'ngmore' ) )
        {
            $this->getDoctrine()
                ->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )
                ->removeByHash( $hash );

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

        if ( $form->isValid() )
        {
            $data = $form->getData();

            $userUpdateStruct = $this->userService->newUserUpdateStruct();
            $userUpdateStruct->password = $data[ "password" ];

            $user = $this->getRepository()->sudo(
                function( Repository $repository ) use ( $user, $userUpdateStruct )
                {
                    return $repository->getUserService()->updateUser( $user, $userUpdateStruct );
                }
            );

            $this->mailHelper
                ->sendMail(
                    $user->email,
                    $this->getConfigResolver()->getParameter( 'template.user.mail.forgot_password_password_changed', 'ngmore' ),
                    'ngmore.user.forgot_password.mail.password_changed.subject',
                    array(
                        'user' => $user
                    )
                );

            $this->getDoctrine()
                ->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )
                ->removeByUserId( $user->id );

            return $this->render(
                $this->getConfigResolver()->getParameter( "template.user.reset_password_done", "ngmore" )
            );
        }

        return $this->render(
            $this->getConfigResolver()->getParameter( "template.user.reset_password", "ngmore" ),
            array(
                'form' => $form->createView()
            )
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

        $this->getDoctrine()
            ->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )
            ->removeByUserId( $user->id );

        $this->getDoctrine()
            ->getRepository( 'NetgenMoreBundle:NgUserSetting' )
            ->activateUser( $user->id );
    }
}
