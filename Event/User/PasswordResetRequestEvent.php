<?php

namespace Netgen\Bundle\MoreBundle\Event\User;

use Symfony\Component\EventDispatcher\Event;
use eZ\Publish\API\Repository\Values\User\User;

class PasswordResetRequestEvent extends Event
{
    /**
     * @var \eZ\Publish\API\Repository\Values\User\User
     */
    protected $user;

    /**
     * @var string
     */
    protected $email;

    /**
     * @param string $email
     * @param \eZ\Publish\API\Repository\Values\User\User $user
     */
    public function __construct( $email, User $user = null )
    {
        $this->user = $user;
        $this->email = $email;
    }

    /**
     * @return \eZ\Publish\API\Repository\Values\User\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }
}
