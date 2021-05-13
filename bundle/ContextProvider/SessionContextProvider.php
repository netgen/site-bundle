<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\ContextProvider;

use FOS\HttpCache\UserContext\ContextProviderInterface;
use FOS\HttpCache\UserContext\UserContext;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionContextProvider implements ContextProviderInterface
{
    protected SessionInterface $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * If the session is started, adds the session ID to user context. This allows
     * varying the cache per session.
     */
    public function updateUserContext(UserContext $context): void
    {
        if ($this->session->isStarted()) {
            $context->addParameter('sessionId', $this->session->getId());
        }
    }
}
