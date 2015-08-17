<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     * @param $translator
     * @param \eZ\Publish\API\Repository\UserService $userService
     */
    public function __construct
    (
        ConfigResolverInterface $configResolver,
        TranslatorInterface $translator,
        UserService $userService
    )
    {
        $this->configResolver = $configResolver;
        $this->translator = $translator;
        $this->userService = $userService;
    }

    /**
     * Displays and validates register form.
     * If form is valid, sends activation hash key to the user email
     *
     * @param Request $request
     *
     * @return Response
     */
    public function register( Request $request )
    {
        if ( $this->getRepository()->hasAccess( 'user', 'register' ) !== true )
        {
            throw new AccessDeniedHttpException();
        }

        $contentType = $this->getRepository()->getContentTypeService()->loadContentTypeByIdentifier( "user" );
        $languages = $this->configResolver->getParameter( "languages" );
        $userCreateStruct = $this->userService->newUserCreateStruct(
            null,
            null,
            null,
            $languages[0],
            $contentType
        );
        if ( $this->configResolver->hasParameter( 'user_register.auto_enable', 'ngmore' ) )
        {
            $userCreateStruct->enabled = $this->configResolver->getParameter( 'user_register.auto_enable', 'ngmore' );
        }

        $data = new DataWrapper( $userCreateStruct, $userCreateStruct->contentType );

        $formBuilder = $this->container->get( "form.factory" )->createBuilder(
            "ezforms_create_user",
            $data,
            array(
                "translation_domain" => "ngmore_user"
            )
        );
        $formBuilder->add( "save", "submit", array( "label" => "ngmore.user.register.submit_label" ) );

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
                $currentUser = $this->getRepository()->getCurrentUser();
                $this->getRepository()->setCurrentUser( $this->userService->loadUser( 14 ) );

                $userGroup = $this->userService->loadUserGroup(
                    $this->configResolver->getParameter( "user_register.user_group_content_id", "ngmore" )
                );

                $newUser = $this->userService->createUser(
                    $data->payload,
                    array( $userGroup )
                );

                $this->getRepository()->setCurrentUser( $currentUser );

                $autoEnable = false;
                if ( $this->configResolver->hasParameter( 'user_register.auto_enable', 'ngmore' ) )
                {
                    $autoEnable = $this->configResolver->getParameter( 'user_register.auto_enable', 'ngmore' );
                }
                if ( !$autoEnable )
                {
                    $hash =
                        $this
                            ->getDoctrine()
                            ->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )
                            ->setVerificationHash( $newUser->id );
                    $this->container->get( 'ngmore.helper.mail_helper' )->sendMail( $newUser->email, MailHelper::ACTIVATION, array( 'user' => $newUser, 'hash' => $hash ) );
                }
                else
                {
                    $this->container->get( 'ngmore.helper.mail_helper' )->sendMail( $newUser->email, MailHelper::WELCOME, array( 'user' => $newUser ) );
                }

                return $this->redirect( "login" );
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

    /**
     * Activates the user by hash key
     *
     * @param $hash
     *
     * @return Response
     */
    public function activateUser( $hash )
    {
        $template = $this->configResolver->getParameter( "user_register.template.activate", "ngmore" );

        $accountActivated = false;
        if ( !$alreadyActive = $this->isUserActive( $hash ) )
        {
            if ( $this->isUserActive( $hash ) )
            {
                $accountActivated = false;
            }
            else
            {
                /** @var EzUserAccountKey $result */
                $result = $this
                    ->getDoctrine()
                    ->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )
                    ->getEzUserAccountKeyByHash( $hash );
                $userId = $result->getUserId();
                $user = $this->userService->loadUser( $userId );

                $this->enableUser( $user );

                $accountActivated = true;
            }
        }

        return $this->render(
            $template,
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
                $this->container->get( 'ngmore.helper.mail_helper' )->sendMail( $form->get( 'email' )->getData(), MailHelper::MAILNOTREGISTERED );
            }
            else
            {
                $user = $userArray[ 0 ];

                $hash = $this->getDoctrine()->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )->setVerificationHash( $user->id );
                $this
                    ->container
                    ->get( 'ngmore.helper.mail_helper' )
                    ->sendMail( $user->email, MailHelper::FORGOTTENPASSWORD, array( 'user' => $user, 'hash' => $hash ) );
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
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function resetPassword( Request $request, $hash )
    {
        $template = $this->getConfigResolver()->getParameter( "user_register.template.reset_password", "ngmore" );

        /** @var EzUserAccountKey $result */
        $result = $this->getDoctrine()->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )->getEzUserAccountKeyByHash( $hash );

        if ( empty( $result ) || time() - $result->getTime() > 3600 )
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

                try
                {
                    $this->userService->loadUserByCredentials(
                        $user->login,
                        $data['original_password']
                    );
                }
                catch( NotFoundException $e )
                {
                    return $this->render(
                        $template,
                        array(
                            "errorMessage" => $this->translator->trans(
                                "ngmore.user.forgotten_password.wrong_password",
                                array(),
                                "ngmore_user"
                            ),
                            "form" => $form->createView()
                        )
                    );
                }

                $currentUser = $this->getRepository()->getCurrentUser();
                $this->getRepository()->setCurrentUser( $this->userService->loadUser( 14 ) );

                $user = $this->userService->loadUser( $data["user_id"] );

                $userUpdateStruct = $this->userService->newUserUpdateStruct();
                $userUpdateStruct->password = $data["password"];
                $this->userService->updateUser( $user, $userUpdateStruct );

                $this
                    ->container
                    ->get( 'ngmore.helper.mail_helper' )
                    ->sendMail( $user->email, MailHelper::PASSWORDCHANGED, array( 'user' => $user ) );
                $this
                    ->getDoctrine()
                    ->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )
                    ->removeEzUserAccountKeyByUserId( $user->id );

                $this->getRepository()->setCurrentUser( $currentUser );

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
                    ->add( 'email', 'email', array(
                        "label" => "ngmore.user.forgotten_password.email"
                    ))
                    ->add( 'generate_new_password', 'submit', array(
                        "label" => "ngmore.user.forgotten_password.submit"
                    ))
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
        $originalPasswordOptions = array(
            "required" => true,
            "constraints" => array(
                new Constraints\Length(
                    array(
                        "min" => $this->container->getParameter( "netgen.ezforms.form.type.fieldtype.ezuser.parameters.min_password_length" ),
                    )
                ),
            )
        );

        $passwordOptions = array(
            "type" => "password",
            "required" => false,
            "invalid_message" => "Both passwords must match.",
            "options" => array(
                "constraints" => array(
                    new Constraints\Length(
                        array(
                            "min" => $this->container->getParameter( "netgen.ezforms.form.type.fieldtype.ezuser.parameters.min_password_length" ),
                        )
                    ),
                ),
            ),
            "first_options" => array(
                "label" => "New password",
            ),
            "second_options" => array(
                "label" => "Repeat new password",
            ),
        );

        return $this->createFormBuilder( null, array( "translation_domain" => "ngmore_user" ) )
            ->add( 'user_id', 'hidden', array( 'data' => $user->id ) )
            ->add( 'original_password', 'password', $originalPasswordOptions )
            ->add( 'password', 'repeated', $passwordOptions )
            ->add( 'save', 'submit', array( 'label' => "ngmore.user.reset_password.submit_label") )
            ->getForm();
    }

    /**
     * Checks by hash key if the user is already activated
     *
     * @param $hash
     *
     * @return bool
     */
    protected function isUserActive( $hash )
    {
        return $this
            ->getDoctrine()
            ->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )
            ->getEzUserAccountKeyByHash( $hash ) ? false : true;
    }

    /**
     * Enables the user
     *
     * @param $user
     */
    protected function enableUser( $user )
    {
        $currentUser = $this->getRepository()->getCurrentUser();
        $this->getRepository()->setCurrentUser( $this->userService->loadUser( 14 ) );

        $userUpdateStruct = $this->userService->newUserUpdateStruct();
        $userUpdateStruct->enabled = true;
        $this->userService->updateUser( $user, $userUpdateStruct );

        $this->getRepository()->setCurrentUser( $currentUser );

        $this
            ->getDoctrine()
            ->getRepository( 'NetgenMoreBundle:EzUserAccountKey' )
            ->removeEzUserAccountKeyByUserId( $user->id );

        $this->container->get( 'ngmore.helper.mail_helper' )->sendMail( $user->email, MailHelper::WELCOME, array( 'user' => $user ) );
    }
}
