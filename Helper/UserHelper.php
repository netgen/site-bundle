<?php

namespace Netgen\Bundle\MoreBundle\Helper;

use Swift_Message;
use Swift_SendmailTransport;
use Doctrine\ORM\EntityManager;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\ContentService;
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

    /** @var  \eZ\Publish\API\Repository\ContentService */
    protected $contentService;

    /** @var  \eZ\Publish\API\Repository\LocationService */
    protected $locationService;

    /** @var  \eZ\Publish\API\Repository\Repository */
    protected $repository;

    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    protected $configResolver;

    private $fromMail;

    /** @var  \eZ\Publish\Core\Helper\FieldHelper */
    protected $fieldHelper;

    /** @var  string */
    protected $activationMailTemplate;

    protected $forgottenPasswordMailTemplate;

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
        $this->repository = $repository;
        $this->userService = $repository->getUserService();
        $this->contentService = $repository->getContentService();
        $this->locationService = $repository->getLocationService();
        $this->fromMail = $configResolver->getParameter( 'user_register.mail_sender', 'ngmore' );
        $this->configResolver = $configResolver;
        $this->fieldHelper = $fieldHelper;
        $this->activationMailTemplate = $configResolver->getParameter( 'user_register.activation_mail_template', 'ngmore' );
        $this->forgottenPasswordMailTemplate = $configResolver->getParameter( 'user_register.forgotten_password_mail_template', 'ngmore' );
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

    public function createUser( $data )
    {
        $userGroup = $this->userService->loadUserGroup(
            $this->configResolver->getParameter( "user_register.user_group", "ngmore" )
        );

        return $this->userService->createUser(
            $data->payload,
            array( $userGroup )
        );
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

        $result = $this->getEzUserAccountKeyByHash( $hash );
        $userID = $result['user_id'];
        $user = $this->userService->loadUser( $userID );
        $this->enableUser( $user );

        return true;
    }

    public function setNewPassword( $email )
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
        $result = $this->getEzUserAccountKeyByHash( $hash );

        if ( time() - $result['time'] > 3600 )
        {
            return false;
        }

        return true;
    }

    public function loadUserByHash( $hash )
    {
        $user_account = $this->getEzUserAccountKeyByHash( $hash );
        $userId = $user_account['user_id'];

        return $this->userService->loadUser( $userId );
    }

    /**
     * @param $user
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    private function setVerificationHash( $user )
    {
        $userID = $user->id;
        $hash = md5( mt_rand() . time() . $userID );
        $sql = "INSERT INTO ezuser_accountkey ( hash_key, id, time, user_id ) VALUES ( :hash, :_id, :cur_time, :user_id ) ";
        $connection = $this->em->getConnection();
        $statement = $connection->prepare($sql);
        $statement->bindValue( "hash", $hash );
        $statement->bindValue( "_id", NULL );
        $statement->bindValue( "cur_time", time() );
        $statement->bindValue( "user_id", $userID );
        $statement->execute();
        return $hash;
    }

    private function getEzUserAccountKeyByHash( $hash )
    {
        //@todo wrap in try catch block
        if ( !$hash ) return null;
        $sql = "SELECT * FROM ezuser_accountkey WHERE hash_key=:hash ";
        $connection = $this->em->getConnection();
        $statement = $connection->prepare( $sql );
        $statement->bindValue( "hash", $hash );
        $statement->execute();
        return $statement->fetch();
    }

    private function enableUser( $user )
    {
        $sql = "UPDATE ezuser_setting SET is_enabled=1 WHERE user_id=:user_id ";
        $connection = $this->em->getConnection();
        $statement = $connection->prepare( $sql );
        $statement->bindValue( "user_id", $user->id );
        $statement->execute();
        $this->removeEzUserAccountKeyByUser( $user );
    }

    private function removeEzUserAccountKeyByUser( $user )
    {
        $sql = "DELETE FROM ezuser_accountkey WHERE user_id=:user_id";
        $connection = $this->em->getConnection();
        $statement = $connection->prepare( $sql );
        $statement->bindValue( "user_id", $user->id );
        $statement->execute();
    }

    private function getRootLocation()
    {
        return $this->locationService->loadLocation(
            $this->configResolver->getParameter( 'content.tree_root.location_id' ));
    }
}