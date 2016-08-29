<?php

namespace Netgen\Bundle\MoreBundle\EventListener;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Netgen\Bundle\MoreBundle\Helper\MailHelper;
use Netgen\Bundle\MoreBundle\Entity\Repository\NgUserSettingRepository;
use Netgen\Bundle\MoreBundle\Entity\Repository\EzUserAccountKeyRepository;
use eZ\Publish\Core\Helper\TranslationHelper;

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
     * @var \eZ\Publish\Core\Helper\TranslationHelper
     */
    protected $translationHelper;

    /**
     * @param \Netgen\Bundle\MoreBundle\Helper\MailHelper $mailHelper
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     * @param \Netgen\Bundle\MoreBundle\Entity\Repository\NgUserSettingRepository $ngUserSettingRepository
     * @param \Netgen\Bundle\MoreBundle\Entity\Repository\EzUserAccountKeyRepository
     * @param \eZ\Publish\Core\Helper\TranslationHelper
     */
    public function __construct(
        MailHelper $mailHelper,
        ConfigResolverInterface $configResolver,
        NgUserSettingRepository $ngUserSettingRepository,
        EzUserAccountKeyRepository $ezUserAccountKeyRepository,
        TranslationHelper $translationHelper
    ) {
        $this->mailHelper = $mailHelper;
        $this->configResolver = $configResolver;
        $this->ngUserSettingRepository = $ngUserSettingRepository;
        $this->ezUserAccountKeyRepository = $ezUserAccountKeyRepository;
        $this->translationHelper = $translationHelper;
    }
}
