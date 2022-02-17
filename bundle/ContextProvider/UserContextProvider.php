<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\ContextProvider;

use Ibexa\Contracts\Core\Repository\Repository;
use FOS\HttpCache\UserContext\ContextProvider;
use FOS\HttpCache\UserContext\UserContext;

class UserContextProvider implements ContextProvider
{
    protected Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Adds the current user ID to the user context. Allows varying the caches
     * per user, without taking into the account session for example.
     */
    public function updateUserContext(UserContext $context): void
    {
        $context->addParameter(
            'userId',
            $this->repository->getPermissionResolver()->getCurrentUserReference()->getUserId(),
        );
    }
}
