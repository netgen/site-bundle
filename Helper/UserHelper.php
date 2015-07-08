<?php

namespace Netgen\Bundle\MoreBundle\Helper;

use Netgen\Bundle\MoreBundle\Entity\EzUserAccount;
use Netgen\Bundle\MoreBundle\Helper\MailHelper;
use Doctrine\ORM\EntityManager;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\Helper\FieldHelper;
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


    /** @var  \eZ\Publish\Core\Helper\FieldHelper */
    protected $fieldHelper;


    /** @var \Doctrine\ORM\EntityRepository  */
    protected $accountRepository;

    /**
     * @param MailHelper $mailHelper
     * @param EntityManager $em
     * @param Repository $repository
     * @param ConfigResolverInterface $configResolver
     * @param FieldHelper $fieldHelper
     */
    public function __construct(
        MailHelper $mailHelper,
        EntityManager $em,
        Repository $repository,
        ConfigResolverInterface $configResolver,
        FieldHelper $fieldHelper
    )
    {
        $this->mailHelper = $mailHelper;
        $this->em = $em;
        $this->repository = $repository;
        $this->userService = $repository->getUserService();
        $this->accountRepository = $em->getRepository( 'NetgenMoreBundle:EzUserAccount' );
        $this->configResolver = $configResolver;
        $this->fieldHelper = $fieldHelper;
    }

    /**
     * Sets repository user (default admin)
     *
     * @param int $userId
     */
    public function setRepositoryUser( $userId = 14 )
    {
        $this->repository->setCurrentUser(
            $this->userService->loadUser( $userId )
        );
    }

    /**
     * Creates data wrapper for user create form
     *
     * @param bool $enable
     *
     * @return DataWrapper
     */
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

    /**
     * Creates user content type from matching form data
     *
     * @param $data
     *
     * @return User
     */
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

    /**
     * Updates password of the user with userId
     * Also removes the hash key form ezuser_accountkey table
     *
     * @param $userId
     * @param $password
     */
    public function updateUserPassword( $userId, $password )
    {
        $user = $this->userService->loadUser( $userId );

        $userUpdateStruct = $this->userService->newUserUpdateStruct();
        $userUpdateStruct->password = $password;

        $this->repository->setCurrentUser( $this->userService->loadUser( 14 ) );
        $this->repository->getUserService()->updateUser( $user, $userUpdateStruct );
        $this->repository->setCurrentUser( $this->userService->loadUser( $this->configResolver->getParameter( "anonymous_user_id" ) ) );

        $this->mailHelper->sendPasswordChangedMail( $user );

        $this->removeEzUserAccountKeyByUser( $user );
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
        return $this->mailHelper->sendActivationMail( $user, $hash );
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

        /** @var EzUserAccount $result */
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
            $this->mailHelper->sendEmailNotRegisteredMail( $email );
            return;
        }
        $user = $userArray[0];

        $this->repository->setCurrentUser(
            $this->userService->loadUser( 14 )
        );

        $hash = $this->setVerificationHash( $user );
        $this->mailHelper->sendChangePasswordMail( $user, $hash );
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
        /** @var EzUserAccount $result */
        $result = $this->getEzUserAccountKeyByHash( $hash );

        if ( time() - $result->getTime() > 3600 )
        {
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
        /** @var EzUserAccount $user_account */
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

        $userAccount = new EzUserAccount();
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
     * @return EzUserAccount|null
     */
    protected function getEzUserAccountKeyByHash( $hash )
    {
        $result = $this->accountRepository->findOneBy(
            array(
                'hash' => $hash
            )
        );

        if ( $result instanceof EzUserAccount )
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
        $userUpdateStruct = $this->userService->newUserUpdateStruct();
        $userUpdateStruct->enabled = true;

        $this->repository->setCurrentUser( $this->userService->loadUser( 14 ) );
        $this->repository->getUserService()->updateUser( $user, $userUpdateStruct );
        $this->repository->setCurrentUser( $this->userService->loadUser( $this->configResolver->getParameter( "anonymous_user_id" ) ) );

        $this->removeEzUserAccountKeyByUser( $user );
    }

    /**
     * Removes all data for $user from ezuser_accountkey table
     *
     * @param $user
     */
    protected function removeEzUserAccountKeyByUser( $user )
    {
        $results = $this->accountRepository->findBy(
            array(
                'user_id' => $user->id
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