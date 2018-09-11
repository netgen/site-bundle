<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\EventListener;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\User\User;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Netgen\Bundle\SiteBundle\Entity\Repository\EzUserAccountKeyRepository;
use Netgen\Bundle\SiteBundle\Entity\Repository\NgUserSettingRepository;
use Netgen\Bundle\SiteBundle\Helper\MailHelper;
use Netgen\EzPlatformSiteApi\API\LoadService;

abstract class UserEventListener
{
    /**
     * @var \Netgen\Bundle\SiteBundle\Helper\MailHelper
     */
    protected $mailHelper;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * @var \Netgen\Bundle\SiteBundle\Entity\Repository\NgUserSettingRepository
     */
    protected $ngUserSettingRepository;

    /**
     * @var \Netgen\Bundle\SiteBundle\Entity\Repository\EzUserAccountKeyRepository
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
     */
    protected function getUserName(User $user): string
    {
        $contentInfo = $this->repository->sudo(
            function (Repository $repository) use ($user) {
                return $this->loadService->loadContent($user->id)->contentInfo;
            }
        );

        return $contentInfo->name;
    }
}
