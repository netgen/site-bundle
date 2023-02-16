<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Netgen\Bundle\SiteBundle\Entity\UserAccountKey;

use function hash;
use function random_bytes;
use function time;

final class UserAccountKeyRepository extends EntityRepository
{
    /**
     * Creates a user account key.
     */
    public function create(int $userId): UserAccountKey
    {
        $this->removeByUserId($userId);

        $userAccount = new UserAccountKey();
        $userAccount->setHash(hash('md5', random_bytes(256)));
        $userAccount->setTime(time());
        $userAccount->setUserId($userId);

        $this->getEntityManager()->persist($userAccount);
        $this->getEntityManager()->flush();

        return $userAccount;
    }

    /**
     * Returns user account key by hash.
     */
    public function getByHash(string $hash): ?UserAccountKey
    {
        return $this->findOneBy(['hashKey' => $hash]);
    }

    /**
     * Removes user account key for user specified by $userId.
     */
    public function removeByUserId(int $userId): void
    {
        $results = $this->findBy(['userId' => $userId]);

        foreach ($results as $result) {
            $this->getEntityManager()->remove($result);
        }

        $this->getEntityManager()->flush();
    }

    /**
     * Removes user account key by user hash.
     */
    public function removeByHash(string $hash): void
    {
        $results = $this->findBy(['hashKey' => $hash]);

        foreach ($results as $result) {
            $this->getEntityManager()->remove($result);
        }

        $this->getEntityManager()->flush();
    }
}
