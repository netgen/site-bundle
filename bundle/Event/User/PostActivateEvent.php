<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Event\User;

use eZ\Publish\API\Repository\Values\User\User;
use Symfony\Component\EventDispatcher\Event;

class PostActivateEvent extends Event
{
    /**
     * @var \eZ\Publish\API\Repository\Values\User\User
     */
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
