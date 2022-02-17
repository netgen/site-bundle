<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Core\EventListener;

use Ibexa\Contracts\Core\Repository\Events\User\CreateUserEvent;
use Ibexa\Contracts\Core\Repository\UserService;
use Netgen\Bundle\SiteBundle\Entity\Repository\NgUserSettingRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CreateUserListener implements EventSubscriberInterface
{
    protected UserService $userService;

    protected NgUserSettingRepository $ngUserSettingRepository;

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
