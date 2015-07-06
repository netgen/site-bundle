<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use Symfony\Component\HttpFoundation\Request;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;
use eZ\Publish\API\Repository\Exceptions\InvalidArgumentException;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;

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
                $this->getConfigResolver()->getParameter( "user_register.template", "ngmore" ),
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
                    $user = $registerHelper->createUser( $data );

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
                        $this->getConfigResolver()->getParameter( "user_register.template", "ngmore" ),
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
            $this->getConfigResolver()->getParameter( "user_register.template", "ngmore" ),
            array(
                "form" => $form->createView(),
                "errorMessage" => $errorMessage
            )
        );
    }

    public function activate( $hash )
    {
        $registerHelperService = $this->get( "ngmore.helper.user_helper" );

        $alreadyActive = false;
        $accountActivated = $registerHelperService->verifyUserByHash( $hash );

        if ( !$accountActivated )
        {
            $alreadyActive = $registerHelperService->isUserActive( $hash );
        }

        $template = $this->configResolver->getParameter( "user_activate.template", "ngmore" );

        return $this->render(
            $template,
            array(
                "account_activated" => $accountActivated,
                "already_active" => $alreadyActive
            )
        );
    }
}
