<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\API\Repository\Repository;
use Netgen\Bundle\MoreBundle\Entity\NgUserSetting;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\TranslatorInterface;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use Symfony\Component\Validator\Constraints;
use Netgen\Bundle\EzFormsBundle\Form\DataWrapper;
use Netgen\Bundle\MoreBundle\Helper\MailHelper;
use Netgen\Bundle\MoreBundle\Entity\EzUserAccountKey;
use eZ\Publish\API\Repository\UserService;

class UserController extends Controller
{
    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

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
     * @param \eZ\Publish\API\Repository\Repository $repository
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     * @param \Symfony\Component\Translation\TranslatorInterface $translator
     * @param \Netgen\Bundle\MoreBundle\Helper\MailHelper $mailHelper
     */
    public function __construct
    (
        Repository $repository,
        ConfigResolverInterface $configResolver,
        TranslatorInterface $translator,
        MailHelper $mailHelper
    )
    {
        $this->repository = $repository;
        $this->configResolver = $configResolver;
        $this->translator = $translator;
        $this->userService = $repository->getUserService();
        $this->mailHelper = $mailHelper;

        if ( $this->configResolver->hasParameter( 'user_register.auto_enable', 'ngmore' ) )
        {
            $this->autoEnable= $this->configResolver->getParameter( 'user_register.auto_enable', 'ngmore' );
        }
    }

    /**
     * Displays and validates register form.
     * If form is valid, sends activation hash key to the user email
     *
     * @param Request $request
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function register( Request $request )
    {
        if ( $this->repository->hasAccess( 'user', 'register' ) !== true )
        {
            throw new AccessDeniedHttpException();
        }

        $userContentTypeIdentifier = $this->configResolver->getParameter( 'user_register.user_content_type_identifier', 'ngmore' );
        $userContentType = $this->repository->getContentTypeService()->loadContentTypeByIdentifier( $userContentTypeIdentifier );
        $languages = $this->configResolver->getParameter( "languages" );
        $userCreateStruct = $this->userService->newUserCreateStruct(
            null,
            null,
            null,
            $languages[0],
            $userContentType
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
                $errorMessage = $this->translator->trans(
                    "ngmore.user.register.email_already_in_use",
                    array(),
                    "ngmore_user"
                );

                return $this->render(
                    $this->getConfigResolver()->getParameter( "user_register.template.register", "ngmore" ),
                    array(
                        "form" => $form->createView(),
                        "errorMessage" => $errorMessage
                    )
                );
            }

            try
            {
                $this->userService->loadUserByLogin( $form->getData()->payload->login );

                $errorMessage = $this->translator->trans(
                    "ngmore.user.register.username_taken",
                    array(),
                    "ngmore_user"
                );

                return $this->render(
                    $this->getConfigResolver()->getParameter( "user_register.template.register", "ngmore" ),
                    array(
                        "form" => $form->createView(),
                        "errorMessage" => $errorMessage
                    )
                );
            }
            catch( NotFoundException $e )
            {
                // do nothing
            }

            try
            {
                $repository = $this->repository;
                $userGroupId = $this->configResolver->getParameter( 'user_register.user_group_content_id', 'ngmore' );

                $newUser = $this->repository->sudo(
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
                            $this->configResolver->getParameter( 'user_register.template.mail.welcome', 'ngmore' ),
                            $this->translator->trans( "ngmore.user.register.mail.subject", array(), "ngmore_user" ),
                            array(
                                'user' => $newUser
                            )
                        );

                    return $this->render(
                        $this->getConfigResolver()->getParameter( 'user_register.template.register_success', 'ngmore' )
                    );
                }
                else
                {
                    $hash =
                        $this
                            ->getDoctrine()
                            ->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )
                            ->createVerificationHash( $newUser->id );

                    $this->mailHelper
                        ->sendMail(
                            $newUser->email,
                            $this->configResolver->getParameter( 'user_register.template.mail.activation', 'ngmore' ),
                            $this->translator->trans( "ngmore.user.activate.mail.subject", array(), "ngmore_user" ),
                            array(
                                'user' => $newUser,
                                'hash' => $hash
                            )
                        );

                    return $this->render(
                        $this->getConfigResolver()->getParameter( 'user_register.template.activation_mail_sent', 'ngmore' )
                    );
                }
            }
            catch ( NotFoundException $e )
            {
                $errorMessage = $this->translator->trans(
                    "ngmore.user.register.general_error",
                    array(),
                    "ngmore_user"
                );

                return $this->render(
                    $this->getConfigResolver()->getParameter( "user_register.template.register", "ngmore" ),
                    array(
                        "form" => $form->createView(),
                        "errorMessage" => $errorMessage
                    )
                );
            }
        }

        return $this->render(
            $this->getConfigResolver()->getParameter( "user_register.template.register", "ngmore" ),
            array(
                "form" => $form->createView()
            )
        );
    }

    public function resendActivationMail( Request $request )
    {
        $accountRepository = $this->getDoctrine()->getRepository( 'NetgenMoreBundle:EzUserAccountKey' );

        $form =  $this->createFormBuilder( null, array( "translation_domain" => "ngmore_user" ) )
                      ->add(
                          'email',
                          'email',
                          array(
                              'constraints' => array(
                                  new Constraints\Email(),
                                  new Constraints\NotBlank()
                              )
                          )
                      )
                      ->getForm();

        $form->handleRequest( $request );

        if ( $form->isValid() )
        {
            $userArray = $this->userService->loadUsersByEmail( $form->get( 'email' )->getData() );

            if ( empty( $userArray ) )
            {
                $this->mailHelper->sendMail(
                    $form->get( 'email' )->getData(),
                    $this->configResolver->getParameter( 'user_register.template.mail.activation_mail_not_registered', 'ngmore' ),
                    $this->translator->trans( "ngmore.user.activate.mail.activation_mail_not_registered.subject", array(), "ngmore_user" )
                );

                return $this->render(
                    $this->getConfigResolver()->getParameter( 'user_register.template.activation_mail_sent', 'ngmore' )
                );
            }

            if ( $userArray[0]->enabled ||
                $this->getDoctrine()->getRepository( 'NetgenMoreBundle:NgUserSetting' )->isUserIdActivated( $userArray[0]->id ) )
            {
                $this->mailHelper->sendMail(
                    $form->get( 'email' )->getData(),
                    $this->configResolver->getParameter( 'user_register.template.mail.user_already_active', 'ngmore' ),
                    $this->translator->trans( "ngmore.user.activate.mail.user_already_active.subject", array(), "ngmore_user" ),
                    array(
                        'user' => $userArray[0]
                    )
                );
            }
            else
            {
                $user = $userArray[0];
                $newHash = $accountRepository->createVerificationHash( $user->id );

                $this->mailHelper
                    ->sendMail(
                        $user->email,
                        $this->configResolver->getParameter( 'user_register.template.mail.activation', 'ngmore' ),
                        $this->translator->trans( "ngmore.user.activate.mail.subject", array(), "ngmore_user" ),
                        array(
                            'user' => $user,
                            'hash' => $newHash
                        )
                    );
            }

            return $this->render(
                $this->getConfigResolver()->getParameter( 'user_register.template.activation_mail_sent', 'ngmore' )
            );
        }

        return $this->render(
            $this->getConfigResolver()->getParameter( 'user_register.template.activation_mail_sent', 'ngmore' ),
            array( 'form' => $form->createView() )
        );
    }

    /**
     * Activates the user by hash key
     *
     * @param $hash
     *
     * @return Response
     */
    public function activateUser( $hash )
    {
        if ( !$this->getDoctrine()->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )->hashExists( $hash ) )
        {
            throw new NotFoundHttpException();
        }

        /** @var EzUserAccountKey $result */
        $result = $this
            ->getDoctrine()
            ->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )
            ->getEzUserAccountKeyByHash( $hash );

        if ( time() - $result->getTime() > $this->configResolver->getParameter( 'user_register.activate_hash_validity_time', 'ngmore' ) )
        {
            $this->getDoctrine()->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )->removeEzUserAccountKeyByHash( $hash );

            // @todo: better message?
            throw new NotFoundHttpException();
        }

        $userId = $result->getUserId();
        $user = $this->userService->loadUser( $userId );

        $accountActivated = false;
        $alreadyActive = false;
        if ( $user->enabled )
        {
            $alreadyActive = true;
        }
        else
        {
            $this->enableUser( $user );

            $this->mailHelper
                ->sendMail(
                    $user->email,
                    $this->configResolver->getParameter( 'user_register.template.mail.welcome', 'ngmore' ),
                    $this->translator->trans( "ngmore.user.register.mail.subject", array(), "ngmore_user" ),
                    array(
                        'user' => $user
                    )
                );

            $accountActivated = true;
        }

        return $this->render(
            $this->configResolver->getParameter( "user_register.template.activate", "ngmore" ),
            array(
                "account_activated" => $accountActivated,
                "already_active" => $alreadyActive
            )
        );
    }

    /**
     * Displays and validates forgotten password form.
     * If form is valid, sends mail to the user with hash key
     *
     * @param Request $request
     *
     * @return Response
     */
    public function forgotPassword( Request $request )
    {
        $form = $this->createForgotPassForm();
        $form->handleRequest( $request );

        if ( $form->isValid() )
        {
            $userArray = $this->userService->loadUsersByEmail( $form->get( 'email' )->getData() );
            if( empty( $userArray ) )
            {
                $this->mailHelper
                    ->sendMail(
                        $form->get( 'email' )->getData(),
                        $this->configResolver->getParameter( 'user_register.template.mail.email_not_registered', 'ngmore' ),
                        $this->translator->trans( "ngmore.user.forgotten_password.mail.email_not_registered.subject", array(), "ngmore_user" )
                    );
            }
            elseif( !$userArray[0]->enabled )
            {
                if ( $this->getDoctrine()->getRepository( 'NetgenMoreBundle:NgUserSetting' )->isUserIdActivated( $userArray[0]->id ) )
                {
                    $this->mailHelper
                        ->sendMail(
                            $form->get( 'email' )->getData(),
                            $this->configResolver->getParameter( 'user_register.template.mail.user_disabled', 'ngmore' ),
                            $this->translator->trans( "ngmore.user.forgotten_password.mail.user_disabled.subject", array(), "ngmore_user" ),
                            array(
                                'user' => $userArray[0],
                            )
                        );
                }
                else
                {
                    $this->mailHelper
                        ->sendMail(
                            $form->get( 'email' )->getData(),
                            $this->configResolver->getParameter( 'user_register.template.mail.user_not_activated', 'ngmore' ),
                            $this->translator->trans( "ngmore.user.forgotten_password.mail.user_not_activated.subject", array(), "ngmore_user" ),
                            array(
                                'user' => $userArray[0],
                            )
                        );
                }
            }
            else
            {
                $user = $userArray[0];

                $hash = $this->getDoctrine()->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )->createVerificationHash( $user->id );
                $this->mailHelper
                    ->sendMail(
                        $user->email,
                        $this->configResolver->getParameter( 'user_register.template.mail.forgotten_password', 'ngmore' ),
                        $this->translator->trans( "ngmore.user.forgotten_password.mail.change_requested.subject", array(), "ngmore_user" ),
                        array(
                            'user' => $user,
                            'hash' => $hash
                        )
                    );
            }

            return $this->render(
                $this->getConfigResolver()->getParameter( 'user_register.template.forgotten_password', 'ngmore' )
            );
        }

        return $this->render(
            $this->getConfigResolver()->getParameter( 'user_register.template.forgotten_password', 'ngmore' ),
            array(
                'form' => $form->createView()
            )
        );
    }

    /**
     * Displays and validates reset password form if the
     * hash key is valid
     *
     * @param Request $request
     * @param $hash
     *
     * @throws \Exception
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function resetPassword( Request $request, $hash )
    {
        $template = $this->getConfigResolver()->getParameter( "user_register.template.reset_password", "ngmore" );

        /** @var EzUserAccountKey $result */
        $result = $this->getDoctrine()->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )->getEzUserAccountKeyByHash( $hash );

        if ( empty( $result ) ||
            time() - $result->getTime() > $this->configResolver->getParameter( 'user_register.forgotpassword_hash_validity_time', 'ngmore' ) )
        {
            $this->getDoctrine()->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )->removeEzUserAccountKeyByHash( $hash );

            return $this->render(
                $template,
                array(
                    "errorMessage" => $this->translator->trans(
                        "ngmore.user.forgotten_password.wrong_hash",
                        array(),
                        "ngmore_user"
                    ),
                )
            );
        }
        else
        {
            /** @var EzUserAccountKey $user_account */
            $user_account = $this->getDoctrine()->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )->getEzUserAccountKeyByHash( $hash );
            $userId = $user_account->getUserId();

            $user = $this->userService->loadUser( $userId );

            $form = $this->createResetPasswordForm( $user );
            $form->handleRequest( $request );

            if ( $form->isValid() )
            {
                $data = $form->getData();

                $repository = $this->getRepository();

                $userUpdateStruct = $this->userService->newUserUpdateStruct();
                $userUpdateStruct->password = $data[ "password" ];

                $user = $repository->sudo(
                    function( Repository $repository ) use ( $user, $userUpdateStruct )
                    {
                        return $repository->getUserService()->updateUser( $user, $userUpdateStruct );
                    }
                );

                $this->mailHelper
                    ->sendMail(
                        $user->email,
                        $this->configResolver->getParameter( 'user_register.template.mail.password_changed', 'ngmore' ),
                        $this->translator->trans( "ngmore.user.forgotten_password.mail.password_changed.subject", array(), "ngmore_user" ),
                        array(
                            'user' => $user
                        )
                    );

                $this
                    ->getDoctrine()
                    ->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )
                    ->removeEzUserAccountKeyByUserId( $user->id );

                return $this->redirect( $this->generateUrl( "login" ) );
            }

            return $this->render(
                $template,
                array(
                    'form' => $form->createView()
                )
            );
        }
    }

    /**
     * Creates Forgotten Password form
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function createForgotPassForm()
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
                    )
                    ->getForm();
    }

    /**
     * Creates Reset Password form
     *
     * @param $user
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function createResetPasswordForm( $user )
    {
        $passwordOptions = array(
            "type" => "password",
            "required" => true,
            "options" => array(
                "constraints" => array(
                    new Constraints\Length(
                        array(
                            "min" => $this->container->getParameter( "netgen.ezforms.form.type.fieldtype.ezuser.parameters.min_password_length" ),
                        )
                    ),
                    new Constraints\NotBlank()
                ),
            )
        );

        return $this->createFormBuilder( null, array( "translation_domain" => "ngmore_user" ) )
            ->add( 'password', 'repeated', $passwordOptions )
            ->getForm();
    }

    /**
     * Enables the user
     *
     * @param $user
     *
     * @throws \Exception
     */
    protected function enableUser( $user )
    {
        $userUpdateStruct = $this->userService->newUserUpdateStruct();
        $userUpdateStruct->enabled = true;

        $repository = $this->getRepository();

        $user = $repository->sudo(
            function( Repository $repository ) use ( $user, $userUpdateStruct )
            {
                return $repository->getUserService()->updateUser( $user, $userUpdateStruct );
            }
        );

        $this
            ->getDoctrine()
            ->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )
            ->removeEzUserAccountKeyByUserId( $user->id );

        $this->getDoctrine()->getRepository( 'NetgenMoreBundle:NgUserSetting' )->activateUserId( $user->id );
    }
}
