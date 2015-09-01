<?php

namespace Netgen\Bundle\MoreBundle\Core\Slot;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
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

    /**
     * Constructor
     *
     * @param \eZ\Publish\API\Repository\UserService $userService
     * @param \Netgen\Bundle\MoreBundle\Entity\Repository\NgUserSettingRepository $ngUserSettingRepository
     */
    public function __construct( UserService $userService, NgUserSettingRepository $ngUserSettingRepository )
    {
        $this->userService = $userService;
        $this->ngUserSettingRepository= $ngUserSettingRepository;
    }

    /**
     * Receive the given $signal and react on it
     *
     * @param \eZ\Publish\Core\SignalSlot\Signal $signal
     */
    public function receive( Signal $signal )
    {
        if ( !$signal instanceof Signal\UserService\CreateUserSignal )
        {
            return;
        }

        try
        {
            $user = $this->userService->loadUser( $signal->userId );
        }
        catch ( NotFoundException $e )
        {
            return;
        }

        if ( $user->enabled )
        {
            $this->ngUserSettingRepository->activateUser( $signal->userId );
        }
    }
}
