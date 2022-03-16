<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\ContextProvider;

use FOS\HttpCache\UserContext\ContextProvider;
use FOS\HttpCache\UserContext\UserContext;
use Ibexa\Contracts\Core\Repository\PermissionService;

class UserContextProvider implements ContextProvider
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Adds the current user ID to the user context. Allows varying the caches
     * per user, without taking into the account session for example.
     */
    public function updateUserContext(UserContext $context): void
    {
        $context->addParameter(
            'userId',
            $this->permissionService->getCurrentUserReference()->getUserId(),
        );
    }
}
