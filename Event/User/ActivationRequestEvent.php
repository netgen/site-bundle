<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Event\User;

use eZ\Publish\API\Repository\Values\User\User;
use Symfony\Component\EventDispatcher\Event;

class ActivationRequestEvent extends Event
{
    /**
     * @var string
     */
    protected $email;

    /**
     * @var \eZ\Publish\API\Repository\Values\User\User
     */
    protected $user;

    /**
     * @param \eZ\Publish\API\Repository\Values\User\User $user
     * @param string $email
     */
    public function __construct($email, User $user = null)
    {
        $this->email = $email;
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return \eZ\Publish\API\Repository\Values\User\User User
     */
    public function getUser()
    {
        return $this->user;
    }
}
