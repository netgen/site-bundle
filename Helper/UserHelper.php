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
        $userService = $this->userService;
        /** @var \eZ\Publish\API\Repository\Values\User\User $user */
        $userArray = $userService->loadUsersByEmail( $email );
        if( empty($userArray) )
        {
            return;
        }
        $user = $userArray[0];

        //login admin so we can change password
        $this->repository->setCurrentUser(
            $userService->loadUserByLogin( "admin" )
        );
        $newPass = $this->generatePassword( 8 );
        $userUpdateStruct = $userService->newUserUpdateStruct();
        $userUpdateStruct->password = $newPass;
        $userUpdateStruct->enabled = true;
        //update user
        $user = $userService->updateUser( $user, $userUpdateStruct );
        //send user a notification mail
        $this->sendChangePasswordMail( $user, $newPass );
    }

    public function sendChangePasswordMail( \eZ\Publish\API\Repository\Values\User\User $user, $password )
    {
        $emailTo = $user->email;
        // get template
        $templateFile = "GenericBundle:mails:change_password.html.twig";
        $templateContent = $this->twig->loadTemplate( $templateFile );
        // Render the whole template including any layouts etc
        $body = $templateContent->render(
            array(
                'user' => $user,
                'root_location' => $this->getRootLocation(),
                'password' => $password
            )
        );
        // Get the subject from template block subject
        $subject = trim( $templateContent->hasBlock( "subject" )
                             ? $templateContent->renderBlock( "subject", array() )
                             : "Default change password mail subject" );
        // Send email
        $message = Swift_Message::newInstance()
                                ->setSubject( $subject )
                                ->setFrom( $this->fromMail )
                                ->setTo( $emailTo )
                                ->setBody( $body, 'text/html' )
        ;
        return $this->mailer->send( $message );
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


    private function generatePassword( $passwordLength, $seed = false )
    {
        $chars = 0;
        $password = '';
        if ( $passwordLength < 1 )
            $passwordLength = 1;
        $decimal = 0;
        while ( $chars < $passwordLength )
        {
            if ( $seed == false )
                $seed = time() . ":" . mt_rand();
            $text = md5( $seed );
            $characterTable = self::passwordCharacterTable();
            $tableCount = count( $characterTable );
            for ( $i = 0; ( $chars < $passwordLength ) and $i < 32; ++$chars, $i += 2 )
            {
                $decimal += hexdec( substr( $text, $i, 2 ) );
                $index = ( $decimal % $tableCount );
                $character = $characterTable[$index];
                $password .= $character;
            }
            $seed = false;
        }
        return $password;
    }

    static function passwordCharacterTable()
    {
        $table = array_merge( range( 'a', 'z' ), range( 'A', 'Z' ), range( 0, 9 ) );
        $specialCharacters = '!#%&{[]}+?;:*';
        $table = array_merge( $table, preg_split( '//', $specialCharacters, -1, PREG_SPLIT_NO_EMPTY ) );
        // Remove some characters that are too similar visually
        $table = array_diff( $table, array( 'I', 'l', 'o', 'O', '0' ) );
        $tableTmp = $table;
        $table = array();
        foreach ( $tableTmp as $item )
        {
            $table[] = $item;
        }
        return $table;
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