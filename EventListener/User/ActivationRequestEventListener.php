<?php

namespace Netgen\Bundle\MoreBundle\EventListener\User;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Netgen\Bundle\MoreBundle\Event\User\ActivationRequestEvent;
use Netgen\Bundle\MoreBundle\Helper\MailHelper;
use Netgen\Bundle\MoreBundle\Entity\Repository\NgUserSettingRepository;
use Netgen\Bundle\MoreBundle\Entity\Repository\EzUserAccountKeyRepository;
use eZ\Publish\API\Repository\Values\User\User;

class ActivationRequestEventListener
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

    /**
     * Listens for the start of the activation process.
     * Event contains information about the submitted email and the user, if found.
     *
     * @param \Netgen\Bundle\MoreBundle\Event\User\ActivationRequestEvent $event
     */
    public function onActivationRequest( ActivationRequestEvent $event )
    {
        $user = $event->getUser();
        $email = $event->getEmail();

        if ( !$user instanceof User )
        {
            $this->mailHelper->sendMail(
                $email,
                $this->configResolver->getParameter( 'template.user.mail.activate_not_registered', 'ngmore' ),
                'ngmore.user.activate.not_registered.subject'
            );

            return;
        }

        if ( $user->enabled )
        {
            $this->mailHelper->sendMail(
                $email,
                $this->configResolver->getParameter( 'template.user.mail.activate_already_active', 'ngmore' ),
                'ngmore.user.activate.already_active.subject',
                array(
                    'user' => $user
                )
            );

            return;
        }

        if ( $this->ngUserSettingRepository->isUserActivated( $user->id ) )
        {
            $this->mailHelper->sendMail(
                $email,
                $this->configResolver->getParameter( 'template.user.mail.activate_disabled', 'ngmore' ),
                'ngmore.user.activate.disabled.subject',
                array(
                    'user' => $user
                )
            );

            return;
        }

        $accountKey = $this->ezUserAccountKeyRepository->create( $user->id );

        $this->mailHelper
            ->sendMail(
                $user->email,
                $this->configResolver->getParameter( 'template.user.mail.activate', 'ngmore' ),
                'ngmore.user.activate.subject',
                array(
                    'user' => $user,
                    'hash' => $accountKey->getHash()
                )
            );
    }
}
