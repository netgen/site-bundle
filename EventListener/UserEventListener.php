<?php

namespace Netgen\Bundle\MoreBundle\EventListener;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\User\User;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Netgen\Bundle\MoreBundle\Entity\Repository\EzUserAccountKeyRepository;
use Netgen\Bundle\MoreBundle\Entity\Repository\NgUserSettingRepository;
use Netgen\Bundle\MoreBundle\Helper\MailHelper;
use Netgen\EzPlatformSiteApi\API\LoadService;

abstract class UserEventListener
{
    /**
     * @var \Netgen\Bundle\MoreBundle\Helper\MailHelper
     */
    protected $mailHelper;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var \Netgen\Bundle\MoreBundle\Entity\Repository\NgUserSettingRepository
     */
    protected $ngUserSettingRepository;

    /**
     * @var \Netgen\Bundle\MoreBundle\Entity\Repository\EzUserAccountKeyRepository
     */
    protected $ezUserAccountKeyRepository;

    /**
     * @var \Netgen\EzPlatformSiteApi\API\LoadService
     */
    protected $loadService;

    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * @param \Netgen\Bundle\MoreBundle\Helper\MailHelper $mailHelper
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     * @param \Netgen\Bundle\MoreBundle\Entity\Repository\NgUserSettingRepository $ngUserSettingRepository
     * @param \Netgen\Bundle\MoreBundle\Entity\Repository\EzUserAccountKeyRepository $ezUserAccountKeyRepository
     * @param \Netgen\EzPlatformSiteApi\API\LoadService $loadService
     * @param \eZ\Publish\API\Repository\Repository $repository
     */
    public function __construct(
        MailHelper $mailHelper,
        ConfigResolverInterface $configResolver,
        NgUserSettingRepository $ngUserSettingRepository,
        EzUserAccountKeyRepository $ezUserAccountKeyRepository,
        LoadService $loadService,
        Repository $repository
    ) {
        $this->mailHelper = $mailHelper;
        $this->configResolver = $configResolver;
        $this->ngUserSettingRepository = $ngUserSettingRepository;
        $this->ezUserAccountKeyRepository = $ezUserAccountKeyRepository;
        $this->loadService = $loadService;
        $this->repository = $repository;
    }

    /**
     * Returns the translated user name.
     *
     * @param \eZ\Publish\API\Repository\Values\User\User $user
     *
     * @return string
     */
    protected function getUserName(User $user)
    {
        $contentInfo = $this->repository->sudo(
            function (Repository $repository) use ($user) {
                return $this->loadService->loadContent($user->id)->contentInfo;
            }
        );

        return $contentInfo->name;
    }
}
