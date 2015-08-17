<?php

namespace Netgen\Bundle\MoreBundle\Helper;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Values\User\UserCreateStruct;
use Netgen\Bundle\MoreBundle\Entity\EzUserAccountKey;
use Netgen\Bundle\MoreBundle\Helper\MailHelper;
use Doctrine\ORM\EntityManager;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\API\Repository\Values\User\User;
use Netgen\Bundle\EzFormsBundle\Form\DataWrapper;

class UserHelper
{
    /** @var  \Netgen\Bundle\MoreBundle\Helper\MailHelper */
    protected $mailHelper;

    /** @var  \Doctrine\ORM\EntityManager */
    protected $em;

    /** @var  \eZ\Publish\API\Repository\Repository */
    protected $repository;

    /** @var \eZ\Publish\API\Repository\UserService  */
    protected $userService;

    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    protected $configResolver;

    /** @var \Doctrine\ORM\EntityRepository  */
    protected $accountRepository;

    /** @var  bool */
    protected $auto_enable;

    /**
     * @param MailHelper $mailHelper
     * @param EntityManager $em
     * @param Repository $repository
     * @param ConfigResolverInterface $configResolver
     */
    public function __construct(
        MailHelper $mailHelper,
        EntityManager $em,
        Repository $repository,
        ConfigResolverInterface $configResolver
    )
    {
        $this->mailHelper = $mailHelper;
        $this->em = $em;
        $this->repository = $repository;
        $this->userService = $repository->getUserService();
        $this->accountRepository = $em->getRepository( 'NetgenMoreBundle:EzUserAccountKey' );
        $this->configResolver = $configResolver;
        if ( $configResolver->hasParameter( 'user_register.auto_enable', 'ngmore' ) )
        {
            $this->auto_enable = $configResolver->getParameter( 'user_register.auto_enable', 'ngmore' );
        }
    }

    /**
     * Creates data wrapper for user create form
     *
     * @return DataWrapper
     */
    public function userCreateDataWrapper()
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
        $userCreateStruct->enabled = $this->auto_enable;

        return new DataWrapper( $userCreateStruct, $userCreateStruct->contentType );
    }

    /**
     * Creates data wrapper for user update form
     *
     * @param $user
     *
     * @return DataWrapper
     */
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

    /**
     * Checks whether email is already registered
     *
     * @param $email
     *
     * @return bool
     */
    public function userEmailExists( $email )
    {
        $users = $this->userService->loadUsersByEmail( $email );

        if ( count( $users ) > 0 )
        {
            return true;
        }

        return false;
    }

    public function userLoginExists( UserCreateStruct $user )
    {
        try
        {
            $this->userService->loadUserByLogin( $user->login );

            return true;
        }
        catch( NotFoundException $e )
        {
            return false;
        }
    }

    /**
     * Creates user content type from matching form data
     *
     * @param $data
     *
     * @return User
     */
    public function createUserFromData( $data )
    {
        $currentUser = $this->repository->getCurrentUser();
        $this->repository->setCurrentUser( $this->userService->loadUser( 14 ) );

        $userGroup = $this->userService->loadUserGroup(
            $this->configResolver->getParameter( "user_register.user_group_content_id", "ngmore" )
        );

        $newUser = $this->userService->createUser(
            $data->payload,
            array( $userGroup )
        );

        $this->repository->setCurrentUser( $currentUser );

        return $newUser;
    }

    /**
     * Updates password of the user with userId
     * Also removes the hash key form ezuser_accountkey table
     *
     * @param $userId
     * @param $password
     */
    public function updateUserPassword( $userId, $password )
    {
        $currentUser = $this->repository->getCurrentUser();
        $this->repository->setCurrentUser( $this->userService->loadUser( 14 ) );

        $user = $this->userService->loadUser( $userId );

        $userUpdateStruct = $this->userService->newUserUpdateStruct();
        $userUpdateStruct->password = $password;
        $this->repository->getUserService()->updateUser( $user, $userUpdateStruct );

        $this->mailHelper->sendMail( $user->email, MailHelper::PASSWORDCHANGED, array( 'user' => $user ) );
        $this->removeEzUserAccountKeyByUserId( $user->id );

        $this->repository->setCurrentUser( $currentUser );
    }

    public function activateUser( $user )
    {
        if ( !$this->auto_enable )
        {
            $this->sendActivationCode( $user );
        }
        else
        {
            $this->mailHelper->sendMail( $user->email, MailHelper::WELCOME, array( 'user' => $user ) );
        }
    }

    /**
     * Sets activation hash key and sends it to the user
     *
     * @param User $user
     * @param null|string $subject
     *
     * @return int
     */
    public function sendActivationCode( User $user )
    {
        $hash = $this->setVerificationHash( $user );
        return $this->mailHelper->sendMail( $user->email, MailHelper::ACTIVATION, array( 'user' => $user, 'hash' => $hash ) );
    }


    /**
     * Checks by hash key if the user is already activated
     *
     * @param $hash
     *
     * @return bool
     */
    public function isUserActive( $hash )
    {
        return $this->getEzUserAccountKeyByHash( $hash ) ? false : true;
    }

    /**
     * validates hash key, loads the user, and starts it activation
     *
     * @param $hash
     *
     * @return bool
     */
    public function verifyUserByHash( $hash )
    {
        if ( $this->isUserActive( $hash ) )
        {
            return false;
        }

        /** @var EzUserAccountKey $result */
        $result = $this->getEzUserAccountKeyByHash( $hash );
        $userID = $result->getUserId();
        $user = $this->userService->loadUser( $userID );
        $this->enableUser( $user );

        return true;
    }

    /**
     * Creates hash key and sends the forgotten password mail to the user
     *
     * @param $email
     */
    public function prepareResetPassword( $email )
    {
        $userArray = $this->userService->loadUsersByEmail( $email );
        if( empty( $userArray ) )
        {
            $this->mailHelper->sendMail( $email, MailHelper::MAILNOTREGISTERED ); // mail not registered
            return;
        }
        $user = $userArray[0];

        $hash = $this->setVerificationHash( $user );
        $this->mailHelper->sendMail( $user->email, MailHelper::FORGOTTENPASSWORD, array( 'user' => $user, 'hash' => $hash ) );
    }

    /**
     * Validates forgotten password hash key
     *
     * @param $hash
     *
     * @return bool
     */
    public function validateResetPassword( $hash )
    {
        /** @var EzUserAccountKey $result */
        $result = $this->getEzUserAccountKeyByHash( $hash );

        if ( empty( $result ) || time() - $result->getTime() > 3600 )
        {
            $this->removeEzUserAccountKeyByHash( $hash );
            return false;
        }

        return true;
    }

    /**
     * Loads user by hash key
     *
     * @param $hash
     *
     * @return User
     */
    public function loadUserByHash( $hash )
    {
        /** @var EzUserAccountKey $user_account */
        $user_account = $this->getEzUserAccountKeyByHash( $hash );
        $userId = $user_account->getUserId();

        return $this->userService->loadUser( $userId );
    }

    /**
     * Creates verification hash key
     *
     * @param $user
     *
     * @return string|bool
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function setVerificationHash( $user )
    {
        $userID = $user->id;
        $hash = md5(
            ( function_exists( "openssl_random_pseudo_bytes" ) ? openssl_random_pseudo_bytes( 32 ) : mt_rand() ) .
            microtime() .
            $userID
        );

        $userAccount = new EzUserAccountKey();
        $userAccount->setHash( $hash );
        $userAccount->setTime( time() );
        $userAccount->setUserId( $user->id );

        $this->em->persist( $userAccount );
        $this->em->flush();

        return $hash;
    }

    /**
     * Gets ezuser_accountkey by hash
     *
     * @param $hash
     *
     * @return EzUserAccountKey|null
     */
    protected function getEzUserAccountKeyByHash( $hash )
    {
        $result = $this->accountRepository->findOneBy(
            array(
                'hashKey' => $hash
            )
        );

        if ( $result instanceof EzUserAccountKey )
        {
            return $result;
        }

        return null;
    }

    /**
     * Enables the user
     *
     * @param $user
     */
    protected function enableUser( $user )
    {
        $currentUser = $this->repository->getCurrentUser();
        $this->repository->setCurrentUser( $this->userService->loadUser( 14 ) );

        $userUpdateStruct = $this->userService->newUserUpdateStruct();
        $userUpdateStruct->enabled = true;
        $this->repository->getUserService()->updateUser( $user, $userUpdateStruct );

        $this->repository->setCurrentUser( $currentUser );

        $this->removeEzUserAccountKeyByUserId( $user->id );
        $this->mailHelper->sendMail( $user->email, MailHelper::WELCOME, array( 'user' => $user ) );
    }

    /**
     * Removes all data for $userId from ezuser_accountkey table
     *
     * @param $userId
     */
    protected function removeEzUserAccountKeyByUserId( $userId )
    {
        $results = $this->accountRepository->findBy(
            array(
                'userId' => $userId
            ),
            array(
                'time' => 'DESC'
            )
        );

        foreach( $results as $result )
        {
            $this->em->remove( $result );
            $this->em->flush();
        }
    }

    /**
     * Removes hash key from ezuser_accountkey table
     *
     * @param $hash
     */
    protected function removeEzUserAccountKeyByHash( $hash )
    {
        $results = $this->accountRepository->findBy(
            array(
                'hashKey' => $hash
            ),
            array(
                'time' => 'DESC'
            )
        );

        foreach( $results as $result )
        {
            $this->em->remove( $result );
            $this->em->flush();
        }
    }
}