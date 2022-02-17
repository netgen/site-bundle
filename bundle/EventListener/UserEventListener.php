<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\EventListener;

use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Netgen\Bundle\SiteBundle\Entity\Repository\UserAccountKeyRepository;
use Netgen\Bundle\SiteBundle\Entity\Repository\NgUserSettingRepository;
use Netgen\Bundle\SiteBundle\Helper\MailHelper;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Netgen\EzPlatformSiteApi\API\Values\ContentInfo;

abstract class UserEventListener
{
    protected MailHelper $mailHelper;

    protected ConfigResolverInterface $configResolver;

    protected NgUserSettingRepository $ngUserSettingRepository;

    protected UserAccountKeyRepository $userAccountKeyRepository;

    protected LoadService $loadService;

    protected Repository $repository;

    public function __construct(
        MailHelper $mailHelper,
        ConfigResolverInterface $configResolver,
        NgUserSettingRepository $ngUserSettingRepository,
        UserAccountKeyRepository $userAccountKeyRepository,
        LoadService $loadService,
        Repository $repository
    ) {
        $this->mailHelper = $mailHelper;
        $this->configResolver = $configResolver;
        $this->ngUserSettingRepository = $ngUserSettingRepository;
        $this->userAccountKeyRepository = $userAccountKeyRepository;
        $this->loadService = $loadService;
        $this->repository = $repository;
    }

    /**
     * Returns the translated user name.
     */
    protected function getUserName(User $user): string
    {
        $contentInfo = $this->repository->sudo(
            fn (Repository $repository): ContentInfo => $this->loadService->loadContent($user->id)->contentInfo,
        );

        return $contentInfo->name;
    }
}
