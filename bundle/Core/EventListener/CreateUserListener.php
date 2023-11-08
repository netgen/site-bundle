<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Core\EventListener;

use Ibexa\Contracts\Core\Repository\Events\User\CreateUserEvent;
use Netgen\Bundle\SiteBundle\Entity\Repository\NgUserSettingRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CreateUserListener implements EventSubscriberInterface
{
    public function __construct(private NgUserSettingRepository $ngUserSettingRepository) {}

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
