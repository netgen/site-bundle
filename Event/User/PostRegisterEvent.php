<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Event\User;

use eZ\Publish\API\Repository\Values\User\User;
use Symfony\Component\EventDispatcher\Event;

class PostRegisterEvent extends Event
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
