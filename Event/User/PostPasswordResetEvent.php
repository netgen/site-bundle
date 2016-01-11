<?php

namespace Netgen\Bundle\MoreBundle\Event\User;

use Symfony\Component\EventDispatcher\Event;
use eZ\Publish\API\Repository\Values\User\User;

class PostPasswordResetEvent extends Event
{
    /**
     * @var \eZ\Publish\API\Repository\Values\User\User
     */
    protected $user;

    /**
     * @param \eZ\Publish\API\Repository\Values\User\User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return \eZ\Publish\API\Repository\Values\User\User
     */
    public function getUser()
    {
        return $this->user;
    }
}
