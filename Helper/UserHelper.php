<?php

namespace Netgen\Bundle\MoreBundle\Helper;

use Netgen\Bundle\MoreBundle\Entity\EzUserAccount;
use Swift_Message;
use Swift_SendmailTransport;
use Doctrine\ORM\EntityManager;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use eZ\Publish\Core\Helper\FieldHelper;
use eZ\Publish\API\Repository\Values\User\User;
use Netgen\Bundle\EzFormsBundle\Form\DataWrapper;

class UserHelper
{
    /** @var \Swift_Mailer  */
    protected $mailer;

    /** @var \Twig_Environment  */
    protected $twig;

    /** @var  \Doctrine\ORM\EntityManager */
    protected $em;

    /** @var  \eZ\Publish\API\Repository\Repository */
    protected $repository;

    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    protected $configResolver;

    protected $fromMail;

    /** @var  \eZ\Publish\Core\Helper\FieldHelper */
    protected $fieldHelper;

    /** @var  string */
    protected $activationMailTemplate;

    protected $forgottenPasswordMailTemplate;

    /** @var \Doctrine\ORM\EntityRepository  */
    protected $accountRepository;

    public function __construct(
        \Swift_Mailer $mailer,
        \Twig_Environment $twig,
        EntityManager $em,
        Repository $repository,
        ConfigResolverInterface $configResolver,
        FieldHelper $fieldHelper
    )
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->em = $em;
        $this->accountRepository = $em->getRepository( 'NetgenMoreBundle:EzUserAccount' );
        $this->repository = $repository;
        $this->userService = $repository->getUserService();
        $this->fromMail = $configResolver->getParameter( 'user_register.mail_sender', 'ngmore' );
        $this->configResolver = $configResolver;
        $this->fieldHelper = $fieldHelper;
        $this->activationMailTemplate = $configResolver->getParameter( 'user_register.template.activation_mail', 'ngmore' );
        $this->forgottenPasswordMailTemplate = $configResolver->getParameter( 'user_register.template.forgotten_password_mail', 'ngmore' );
    }

    public function setRepositoryUser( $userId = 14 )
    {
        $this->repository->setCurrentUser(
            $this->userService->loadUser( $userId )
        );
    }

    public function userCreateDataWrapper( $enable = false )
    {
        $contentType = $this->repository->getContentTypeService()->loadContentTypeByIdentifier( "user" );
        $languages = $this->configResolver->getParameter( "languages" );
        $userCreateStruct = $this->userService->newUserCreateStruct(
            null,
            null,
            null,
            $languages[0],
            $contentType
        );
        $userCreateStruct->enabled = $enable;

        return new DataWrapper( $userCreateStruct, $userCreateStruct->contentType );
    }

    public function userUpdateDataWrapper( $user )
    {
        $languages = $this->configResolver->getParameter( "languages" );
        $contentType = $this->repository->getContentTypeService()->loadContentTypeByIdentifier( "user" );
        $contentUpdateStruct = $this->repository->getContentService()->newContentUpdateStruct();
        $contentUpdateStruct->initialLanguageCode = $languages[0];
        $userUpdateStruct = $this->userService->newUserUpdateStruct();
        $userUpdateStruct->contentUpdateStruct = $contentUpdateStruct;

        return new DataWrapper( $userUpdateStruct, $contentType, $user );
    }

    public function userEmailExists( $email )
    {
        $users = $this->userService->loadUsersByEmail( $email );
        if ( count( $users ) > 0 )
        {
            return true;
        }

        return false;
    }

    public function createUserFromData( $data )
    {
        $userGroup = $this->userService->loadUserGroup(
            $this->configResolver->getParameter( "user_register.user_group", "ngmore" )
        );

        return $this->userService->createUser(
            $data->payload,
            array( $userGroup )
        );
    }

    public function updateUserPassword( $userId, $password )
    {
        $user = $this->userService->loadUser( $userId );

        $userUpdateStruct = $this->userService->newUserUpdateStruct();
        $userUpdateStruct->password = $password;

        $this->repository->setCurrentUser( $this->userService->loadUser( 14 ) );
        $this->repository->getUserService()->updateUser( $user, $userUpdateStruct );
        $this->repository->setCurrentUser( $this->userService->loadUser( $this->configResolver->getParameter( "anonymous_user_id" ) ) );

        $this->removeEzUserAccountKeyByUser( $user );
    }

    public function sendActivationCode( User $user, $subject = null )
    {
        $hash = $this->setVerificationHash( $user );
        return $this->sendActivationMail( $user, $hash, $subject );
    }

    public function sendActivationMail( User $user, $hash, $subject = null )
    {
        $emailTo = $user->email;
        $templateContent = $this->twig->loadTemplate( $this->activationMailTemplate );
        $body = $templateContent->render(
            array(
                'user' => $user,
                'root_location' => $this->getRootLocation(),
                'hash' => $hash
            )
        );
        $subject = $subject ?: 'Activation mail';

        $message = Swift_Message::newInstance()
                                ->setSubject( $subject )
                                ->setFrom( $this->fromMail )
                                ->setTo( $emailTo )
                                ->setBody( $body, 'text/html' )
        ;
        return $this->mailer->send( $message );
    }


    public function isUserActive( $hash )
    {
        return $this->getEzUserAccountKeyByHash( $hash ) ? false : true;
    }

    public function verifyUserByHash( $hash )
    {
        if ( $this->isUserActive( $hash ) )
        {
            return false;
        }

        /** @var EzUserAccount $result */
        $result = $this->getEzUserAccountKeyByHash( $hash );
        $userID = $result->getUserId();
        $user = $this->userService->loadUser( $userID );
        $this->enableUser( $user );

        return true;
    }

    public function prepareResetPassword( $email )
    {
        $userArray = $this->userService->loadUsersByEmail( $email );
        if( empty($userArray) )
        {
            return;
        }
        $user = $userArray[0];

        $this->repository->setCurrentUser(
            $this->userService->loadUser( 14 )
        );

        $hash = $this->setVerificationHash( $user );
        $this->sendChangePasswordMail( $user, $hash );
    }

    public function sendChangePasswordMail( User $user, $hash )
    {
        $templateContent = $this->twig->loadTemplate( $this->forgottenPasswordMailTemplate );
        $body = $templateContent->render(
            array(
                'user' => $user,
                'root_location' => $this->getRootLocation(),
                'hash' => $hash
            )
        );

        $subject = "Password has been changed";
        $message = Swift_Message::newInstance()
                                ->setSubject( $subject )
                                ->setFrom( $this->fromMail )
                                ->setTo( $user->email )
                                ->setBody( $body, 'text/html' )
        ;
        return $this->mailer->send( $message );
    }

    public function validateResetPassword( $hash )
    {
        /** @var EzUserAccount $result */
        $result = $this->getEzUserAccountKeyByHash( $hash );

        if ( time() - $result->getTime() > 3600 )
        {
            return false;
        }

        return true;
    }

    public function loadUserByHash( $hash )
    {
        /** @var EzUserAccount $user_account */
        $user_account = $this->getEzUserAccountKeyByHash( $hash );
        $userId = $user_account->getUserId();

        return $this->userService->loadUser( $userId );
    }

    /**
     * @param $user
     *
     * @return string|bool
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function setVerificationHash( $user )
    {
        $userID = $user->id;
        $hash = md5(
            ( function_exists( "openssl_random_pseudo_bytes" ) ? openssl_random_pseudo_bytes( 32 ) : mt_rand() ) .
            microtime() .
            $userID
        );

        $userAccount = new EzUserAccount();
        $userAccount->setHash( $hash );
        $userAccount->setTime( time() );
        $userAccount->setUserId( $user->id );

        $this->em->persist( $userAccount );
        $this->em->flush();

        return $hash;
    }

    private function getEzUserAccountKeyByHash( $hash )
    {
        $results = $this->accountRepository->findBy(
            array(
                'hash' => $hash
            ) // @todo: sort by time
        );

        if ( !is_array( $results ) || empty( $results ) )
        {
            return null;
        }

        return $results[0];
    }

    private function enableUser( $user )
    {
        $userUpdateStruct = $this->userService->newUserUpdateStruct();
        $userUpdateStruct->enabled = true;

        $this->repository->setCurrentUser( $this->userService->loadUser( 14 ) );
        $this->repository->getUserService()->updateUser( $user, $userUpdateStruct );
        $this->repository->setCurrentUser( $this->userService->loadUser( $this->configResolver->getParameter( "anonymous_user_id" ) ) );

        $this->removeEzUserAccountKeyByUser( $user );
    }

    private function removeEzUserAccountKeyByUser( $user )
    {
        $result = $this->accountRepository->findOneBy(
            array(
                'user_id' => $user->id
            )
        );

        if ( $result )
        {
            $this->em->remove( $result );
            $this->em->flush();
        }
    }

    private function getRootLocation()
    {
        return $this->repository->getLocationService()->loadLocation(
            $this->configResolver->getParameter( 'content.tree_root.location_id' ));
    }
}