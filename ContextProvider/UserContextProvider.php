<?php

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

    /**
     * Constructor.
     *
     * @param \eZ\Publish\API\Repository\Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
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
        $context->addParameter(
            'userId',
            $this->repository->getPermissionResolver()->getCurrentUserReference()->getUserId()
        );
    }
}
