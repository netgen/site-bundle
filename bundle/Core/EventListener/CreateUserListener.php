<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Core\EventListener;

use eZ\Publish\API\Repository\UserService;
use eZ\Publish\Core\Event\User\CreateUserEvent;
use Netgen\Bundle\SiteBundle\Entity\Repository\NgUserSettingRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CreateUserListener implements EventSubscriberInterface
{
    /**
     * @var \eZ\Publish\API\Repository\UserService
     */
    protected $userService;

    /**
     * @var \Netgen\Bundle\SiteBundle\Entity\Repository\NgUserSettingRepository
     */
    protected $ngUserSettingRepository;

    public function __construct(UserService $userService, NgUserSettingRepository $ngUserSettingRepository)
    {
        $this->userService = $userService;
        $this->ngUserSettingRepository = $ngUserSettingRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [CreateUserEvent::class => 'onCreateUser'];
    }

    public function onCreateUser(CreateUserEvent $event): void
    {
        $user = $event->getUser();

        if ($user->enabled) {
            $this->ngUserSettingRepository->activateUser($user->id);
        }
    }
}
