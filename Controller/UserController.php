<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\API\Repository\Values\User\User;
use Symfony\Component\HttpFoundation\Request;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;
use eZ\Publish\API\Repository\Exceptions\InvalidArgumentException;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use Symfony\Component\Validator\Constraints;

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
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     * @param $translator
     */
    public function __construct( ConfigResolverInterface $configResolver, TranslatorInterface $translator )
    {
        $this->configResolver = $configResolver;
        $this->translator = $translator;
    }

    public function register( Request $request )
    {
        $errorMessage = null;
        $auto_enable = false;
        if ( $this->configResolver->hasParameter( 'user_register.auto_enable', 'ngmore' ) )
        {
            $auto_enable = $this->configResolver->getParameter( 'user_register.auto_enable', 'ngmore' );
        }

        if ( $this->configResolver->getParameter( "anonymous_user_id" ) != $this->getRepository()->getCurrentUser()->id )
        {
            $errorMessage = $this->translator->trans(
                "ngmore.user.register.already_logged_in",
                array(
                    '%logout%' => $this->generateUrl( "logout" )
                )
            );
            return $this->render(
                $this->getConfigResolver()->getParameter( "user_register.template.register", "ngmore" ),
                array(
                    "errorMessage" => $errorMessage,
                )
            );
        }

        $registerHelper = $this->get( "ngmore.helper.user_helper" );

        $registerHelper->setRepositoryUser();
        $data = $registerHelper->userCreateDataWrapper( $auto_enable );

        $formBuilder = $this->container->get( "form.factory" )->createBuilder( "ezforms_create_user", $data );
        $formBuilder->add( "save", "submit", array( "label" => "ngmore.user.register.submit_label" ) );

        $form = $formBuilder->getForm();
        $form->handleRequest( $request );

        if ( $form->isValid() )
        {
            if ( !$registerHelper->userEmailExists( $form->getData()->payload->email ) )
            {
                try
                {
                    $user = $registerHelper->createUserFromData( $data );

                    if ( !$auto_enable )
                    {
                        $subject = $this->translator->trans( "ngmore.user.mail.activation.subject" );
                        $registerHelper->sendActivationCode( $user, $subject );
                    }

                    return $this->redirect( "login" );
                }
                catch ( NotFoundException $e )
                {
                    $errorMessage = $this->translator->trans(
                        "ngmore.user.register.general_error"
                    );

                    return $this->render(
                        $this->getConfigResolver()->getParameter( "user_register.template.register", "ngmore" ),
                        array(
                            "errorMessage" => $errorMessage,
                        )
                    );
                }
                catch ( InvalidArgumentException $e )
                {
                    // There is no better way to do this ATM...
                    $existingUsernameMessage = "Argument 'userCreateStruct' is invalid: User with provided login already exists";
                    if ( $e->getMessage() === $existingUsernameMessage )
                    {
                        $errorMessage = $this->translator->trans(
                            "ngmore.user.register.already_exists",
                            array(
                                '%logout%' => $this->generateUrl( "logout" )
                            )
                        );
                    }
                }
            }
            else
            {
                $errorMessage = $this->translator->trans(
                    "ngmore.user.register.email_already_in_use"
                );
            }
        }

        return $this->render(
            $this->getConfigResolver()->getParameter( "user_register.template.register", "ngmore" ),
            array(
                "form" => $form->createView(),
                "errorMessage" => $errorMessage
            )
        );
    }

    public function activateUser( $hash )
    {
        $registerHelperService = $this->get( "ngmore.helper.user_helper" );
        $template = $this->configResolver->getParameter( "user_register.template.activate", "ngmore" );

        $alreadyActive = false;
        $accountActivated = $registerHelperService->verifyUserByHash( $hash );
        if ( !$accountActivated )
        {
            $alreadyActive = $registerHelperService->isUserActive( $hash );
        }

        return $this->render(
            $template,
            array(
                "account_activated" => $accountActivated,
                "already_active" => $alreadyActive
            )
        );
    }

    public function forgotPassword( Request $request )
    {
        $email = '';
        $registerHelperService = $this->get( "ngmore.helper.user_helper" );

        $form = $this->createForgotPassForm();
        $form->handleRequest( $request );

        if ( $form->isValid() )
        {
            $email = $form->get( 'email' )->getData();
            $registerHelperService->prepareResetPassword( $email );
            $form = false;
        }

        return $this->render(
            $this->getConfigResolver()->getParameter( 'user_register.template.forgotten_password', 'ngmore' ),
            array(
                'form' => $form ? $form->createView() : false,
                'email' => $email
            )
        );
    }

    public function resetPassword( Request $request, $hash )
    {
        $registerHelper = $this->get( "ngmore.helper.user_helper" );
        $template = $this->getConfigResolver()->getParameter( "user_register.template.reset_password", "ngmore" );

        if ( $registerHelper->validateResetPassword( $hash ) )
        {
            $user = $registerHelper->loadUserByHash( $hash );

            $form = $this->createResetPasswordForm( $user );
            $form->handleRequest( $request );

            if ( $form->isValid() )
            {
                $data = $form->getData();
                $registerHelper->updateUserPassword(  $data["user_id"], $data["password"] );

                return $this->redirect( $this->generateUrl( "login" ) );
            }

            return $this->render(
                $template,
                array(
                    'form' => $form->createView()
                )
            );
        }
        else
        {
            $errorMessage = $this->translator->trans( "ngmore.user.forgotten_password.wrong_hash" );

            return $this->render(
                $template,
                array(
                    "errorMessage" => $errorMessage,
                )
            );
        }
    }

    protected function createForgotPassForm()
    {
        return $this->createFormBuilder()
                    ->add( 'email', 'email', array(
                        "label" => "ngmore.user.forgotten_password.email"
                    ))
                    ->add( 'generate_new_password', 'submit', array(
                        "label" => "ngmore.user.forgotten_password.submit"
                    ))
                    ->getForm();
    }

    protected function createResetPasswordForm( $user )
    {
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

        return $this->createFormBuilder()
            ->add( 'user_id', 'hidden', array( 'data' => $user->id ) )
            ->add( 'password', 'repeated', $passwordOptions )
            ->add( 'save', 'submit', array( 'label' => "ngmore.user.reset_password.submit_label") )
            ->getForm();
    }
}
