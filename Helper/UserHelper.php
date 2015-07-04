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
    }

    public function setRepositoryUser( $userId = 14 )
    {
        $this->repository->setCurrentUser(
            $this->userService->loadUser( $userId )
        );
    }

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

        // Created user will be enabled by default.
        $userCreateStruct->enabled = true;

        return new DataWrapper( $userCreateStruct, $userCreateStruct->contentType );
    }

    public function createUser( $data )
    {
        $userGroup = $this->userService->loadUserGroup(
            $this->configResolver->getParameter( "user_register.user_group", "ngmore" )
        );

        $this->userService->createUser(
            $data->payload,
            array( $userGroup )
        );
    }


    
}