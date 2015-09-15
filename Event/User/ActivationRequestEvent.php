<?php

namespace Netgen\Bundle\MoreBundle\Event\User;

use Symfony\Component\EventDispatcher\Event;
use eZ\Publish\API\Repository\Values\User\User;

class ActivationRequestEvent extends Event
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
     * @param \eZ\Publish\API\Repository\Values\User\User $user
     * @param string $email
     */
    public function __construct( User $user = null, $email )
    {
        $this->user = $user;
        $this->email = $email;
    }

    /**
     * @return \eZ\Publish\API\Repository\Values\User\User User
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
