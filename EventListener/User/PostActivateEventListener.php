<?php

namespace Netgen\Bundle\MoreBundle\EventListener\User;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Netgen\Bundle\MoreBundle\Event\User\PreActivateEvent;
use Netgen\Bundle\MoreBundle\Helper\MailHelper;
use Netgen\Bundle\MoreBundle\Entity\Repository\NgUserSettingRepository;
use Netgen\Bundle\MoreBundle\Entity\Repository\EzUserAccountKeyRepository;

class PostActivateEventListener
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
     * @var \Netgen\Bundle\MoreBundle\Entity\Repository\EzUserAccountKeyRepository;
     */
    protected $ezUserAccountKeyRepository;

    /**
     * @param MailHelper $mailHelper
     * @param ConfigResolverInterface $configResolver
     * @param \Netgen\Bundle\MoreBundle\Entity\Repository\NgUserSettingRepository $ngUserSettingRepository
     * @param \Netgen\Bundle\MoreBundle\Entity\Repository\EzUserAccountKeyRepository
     */
    public function __construct(
        MailHelper $mailHelper,
        ConfigResolverInterface $configResolver,
        NgUserSettingRepository $ngUserSettingRepository,
        EzUserAccountKeyRepository $ezUserAccountKeyRepository
    )
    {
        $this->mailHelper = $mailHelper;
        $this->configResolver = $configResolver;
        $this->ngUserSettingRepository = $ngUserSettingRepository;
        $this->ezUserAccountKeyRepository = $ezUserAccountKeyRepository;
    }

    public function onPostActivate( PreActivateEvent $event )
    {
        $user = $event->getUser();

        $this->ezUserAccountKeyRepository->removeByUserId( $user->id );
        $this->ngUserSettingRepository->activateUser( $user->id );

        $this->mailHelper
            ->sendMail(
                $user->email,
                $this->configResolver->getParameter( 'template.user.mail.welcome', 'ngmore' ),
                'ngmore.user.welcome.subject',
                array(
                    'user' => $user
                )
            );
    }
}
