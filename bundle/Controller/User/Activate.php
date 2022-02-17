<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller\User;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Netgen\Bundle\SiteBundle\Controller\Controller;
use Netgen\Bundle\SiteBundle\Entity\Repository\UserAccountKeyRepository;
use Netgen\Bundle\SiteBundle\Entity\UserAccountKey;
use Netgen\Bundle\SiteBundle\Event\SiteEvents;
use Netgen\Bundle\SiteBundle\Event\User as UserEvents;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function time;

class Activate extends Controller
{
    protected UserService $userService;

    protected EventDispatcherInterface $eventDispatcher;

    protected UserAccountKeyRepository $accountKeyRepository;

    protected Repository $repository;

    protected ConfigResolverInterface $configResolver;

    public function __construct(
        UserService $userService,
        EventDispatcherInterface $eventDispatcher,
        UserAccountKeyRepository $accountKeyRepository,
        Repository $repository,
        ConfigResolverInterface $configResolver
    ) {
        $this->userService = $userService;
        $this->eventDispatcher = $eventDispatcher;
        $this->accountKeyRepository = $accountKeyRepository;
        $this->repository = $repository;
        $this->configResolver = $configResolver;
    }

    /**
     * Activates the user by hash key.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If hash key does not exist
     */
    public function __invoke(string $hash): Response
    {
        $accountKey = $this->accountKeyRepository->getByHash($hash);

        if (!$accountKey instanceof UserAccountKey) {
            throw $this->createNotFoundException();
        }

        if (time() - $accountKey->getTime() > $this->configResolver->getParameter('user.activate_hash_validity_time', 'ngsite')) {
            $this->accountKeyRepository->removeByHash($hash);

            return $this->render(
                $this->configResolver->getParameter('template.user.activate_done', 'ngsite'),
                [
                    'error' => 'hash_expired',
                ],
            );
        }

        try {
            $user = $this->userService->loadUser($accountKey->getUserId());
        } catch (NotFoundException $e) {
            throw $this->createNotFoundException();
        }

        $userUpdateStruct = $this->userService->newUserUpdateStruct();
        $userUpdateStruct->enabled = true;

        $preActivateEvent = new UserEvents\PreActivateEvent($user, $userUpdateStruct);
        $this->eventDispatcher->dispatch($preActivateEvent, SiteEvents::USER_PRE_ACTIVATE);
        $userUpdateStruct = $preActivateEvent->getUserUpdateStruct();

        $user = $this->repository->sudo(
            static fn (Repository $repository): User => $repository->getUserService()->updateUser($user, $userUpdateStruct),
        );

        $postActivateEvent = new UserEvents\PostActivateEvent($user);
        $this->eventDispatcher->dispatch($postActivateEvent, SiteEvents::USER_POST_ACTIVATE);

        return $this->render(
            $this->configResolver->getParameter('template.user.activate_done', 'ngsite'),
        );
    }
}
