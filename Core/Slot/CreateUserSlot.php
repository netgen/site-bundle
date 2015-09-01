<?php

namespace Netgen\Bundle\MoreBundle\Core\Slot;

use eZ\Publish\Core\SignalSlot\Signal;
use eZ\Publish\Core\SignalSlot\Slot;
use eZ\Publish\API\Repository\UserService;
use Netgen\Bundle\MoreBundle\Entity\Repository\NgUserSettingRepository;

class CreateUserSlot extends Slot
{
    /**
     * @var \eZ\Publish\API\Repository\UserService
     */
    protected $userService;

    /**
     * @var \Netgen\Bundle\MoreBundle\Entity\Repository\NgUserSettingRepository
     */
    protected $ngUserSettingRepository;

    public function __construct( UserService $userService, NgUserSettingRepository $ngUserSettingRepository )
    {
        $this->userService = $userService;
        $this->ngUserSettingRepository= $ngUserSettingRepository;
    }

    public function receive( Signal $signal )
    {
        if ( !$signal instanceof Signal\UserService\CreateUserSignal )
        {
            return;
        }

        $user = $this->userService->loadUser( $signal->userId );

        if ( $user->enabled )
        {
            $this->ngUserSettingRepository->activateUserId( $signal->userId );
        }
    }
}