<?php

namespace Netgen\Bundle\MoreBundle\EventListener\User;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Netgen\Bundle\MoreBundle\Event\User\PrePasswordResetEvent;
use Netgen\Bundle\MoreBundle\Helper\MailHelper;
use Netgen\Bundle\MoreBundle\Entity\Repository\NgUserSettingRepository;
use Netgen\Bundle\MoreBundle\Entity\Repository\EzUserAccountKeyRepository;

class PasswordResetRequestEventListener
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
     * @param \Netgen\Bundle\MoreBundle\Entity\Repository\NgUserSettingRepository
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

    public function onPasswordResetRequest( PrePasswordResetEvent $event )
    {
        $user = $event->getUser();
        $email = $event->getEmail();

        if ( empty( $user ) )
        {
            $this->mailHelper
                ->sendMail(
                    $email,
                    $this->configResolver->getParameter( 'template.user.mail.forgot_password_not_registered', 'ngmore' ),
                    'ngmore.user.forgot_password.not_registered.subject'
                );
        }
        else if ( !$user->enabled )
        {
            if ( $this->ngUserSettingRepository->isUserActivated( $user->id ) )
            {
                $this->mailHelper
                    ->sendMail(
                        $email,
                        $this->configResolver->getParameter( 'template.user.mail.forgot_password_disabled', 'ngmore' ),
                        'ngmore.user.forgot_password.disabled.subject',
                        array(
                            'user' => $user,
                        )
                    );
            }
            else
            {
                $this->mailHelper
                    ->sendMail(
                        $email,
                        $this->configResolver->getParameter( 'template.user.mail.forgot_password_not_active', 'ngmore' ),
                        'ngmore.user.forgot_password.not_active.subject',
                        array(
                            'user' => $user,
                        )
                    );
            }
        }
        else
        {
            $accountKey = $this->ezUserAccountKeyRepository->create( $user->id );

            $this->mailHelper
                ->sendMail(
                    $user->email,
                    $this->configResolver->getParameter( 'template.user.mail.forgot_password', 'ngmore' ),
                    'ngmore.user.forgot_password.subject',
                    array(
                        'user' => $user,
                        'hash' => $accountKey->getHash()
                    )
                );
        }

    }
}
