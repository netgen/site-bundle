<?php

namespace Netgen\Bundle\MoreBundle\EventListener\User;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Netgen\Bundle\MoreBundle\Event\User\PostRegisterEvent;
use Netgen\Bundle\MoreBundle\Helper\MailHelper;
use Netgen\Bundle\MoreBundle\Entity\Repository\EzUserAccountKeyRepository;

class PostRegisterEventListener
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
     * @var \Netgen\Bundle\MoreBundle\Entity\Repository\EzUserAccountKeyRepository
     */
    protected $ezUserAccountKeyRepository;

    /**
     * @param MailHelper $mailHelper
     * @param ConfigResolverInterface $configResolver
     * @param EzUserAccountKeyRepository $ezUserAccountKeyRepository
     */
    public function __construct(
        MailHelper $mailHelper,
        ConfigResolverInterface $configResolver,
        EzUserAccountKeyRepository $ezUserAccountKeyRepository
    )
    {
        $this->mailHelper = $mailHelper;
        $this->configResolver = $configResolver;
        $this->ezUserAccountKeyRepository = $ezUserAccountKeyRepository;
    }

    public function onUserRegistered( PostRegisterEvent $event )
    {
        if ( $this->configResolver->getParameter( 'user.auto_enable', 'ngmore' ) )
        {
            $this->mailHelper
                ->sendMail(
                    $event->getUser()->email,
                    $this->configResolver->getParameter( 'template.user.mail.welcome', 'ngmore' ),
                    'ngmore.user.welcome.subject',
                    array(
                        'user' => $event->getUser()
                    )
                );

            return;
        }

        $accountKey = $this->ezUserAccountKeyRepository->create( $event->getUser()->id );

        $this->mailHelper
            ->sendMail(
                $event->getUser()->email,
                $this->configResolver->getParameter( 'template.user.mail.activate', 'ngmore' ),
                'ngmore.user.activate.subject',
                array(
                    'user' => $event->getUser(),
                    'hash' => $accountKey->getHash()
                )
            );
    }
}
