<?php

namespace Netgen\Bundle\MoreBundle\ContextProvider;

use FOS\HttpCache\UserContext\ContextProviderInterface;
use FOS\HttpCache\UserContext\UserContext;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionContextProvider implements ContextProviderInterface
{
    /**
     * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    protected $session;

    /**
     * Constructor.
     *
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * This function is called before generating the hash of a UserContext.
     *
     * This allow to add a parameter on UserContext or set the whole array of parameters
     *
     * @param \FOS\HttpCache\UserContext\UserContext $context
     */
    public function updateUserContext(UserContext $context)
    {
        if ($this->session->isStarted()) {
            $context->addParameter('sessionId', $this->session->getId());
        }
    }
}
