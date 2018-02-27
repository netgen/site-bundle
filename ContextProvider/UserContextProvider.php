<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\ContextProvider;

use eZ\Publish\API\Repository\Repository;
use FOS\HttpCache\UserContext\ContextProviderInterface;
use FOS\HttpCache\UserContext\UserContext;

class UserContextProvider implements ContextProviderInterface
{
    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Adds the current user ID to the user context. Allows varying the caches
     * per user, without taking into the account session for example.
     */
    public function updateUserContext(UserContext $context)
    {
        $context->addParameter(
            'userId',
            $this->repository->getPermissionResolver()->getCurrentUserReference()->getUserId()
        );
    }
}
