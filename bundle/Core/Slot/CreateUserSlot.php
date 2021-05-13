<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Core\Slot;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\Core\SignalSlot\Signal;
use eZ\Publish\Core\SignalSlot\Slot;
use Netgen\Bundle\SiteBundle\Entity\Repository\NgUserSettingRepository;

class CreateUserSlot extends Slot
{
    protected UserService $userService;

    protected NgUserSettingRepository $ngUserSettingRepository;

    public function __construct(UserService $userService, NgUserSettingRepository $ngUserSettingRepository)
    {
        $this->userService = $userService;
        $this->ngUserSettingRepository = $ngUserSettingRepository;
    }

    public function receive(Signal $signal): void
    {
        if (!$signal instanceof Signal\UserService\CreateUserSignal) {
            return;
        }

        try {
            $user = $this->userService->loadUser($signal->userId);
        } catch (NotFoundException $e) {
            return;
        }

        if ($user->enabled) {
            $this->ngUserSettingRepository->activateUser($signal->userId);
        }
    }
}
