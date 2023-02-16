<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Netgen\Bundle\SiteBundle\Entity\NgUserSetting;

final class NgUserSettingRepository extends EntityRepository
{
    /**
     * Returns if user specified by $userId is activated.
     */
    public function isUserActivated(int $userId): bool
    {
        $ngUserSetting = $this->findOneBy(['userId' => $userId]);

        if ($ngUserSetting instanceof NgUserSetting) {
            return $ngUserSetting->getIsActivated();
        }

        return false;
    }

    /**
     * Activates the user specified by $userId.
     */
    public function activateUser(int $userId): NgUserSetting
    {
        $ngUserSetting = $this->findOneBy(['userId' => $userId]);

        if ($ngUserSetting instanceof NgUserSetting) {
            $ngUserSetting->setIsActivated(true);
        } else {
            $ngUserSetting = new NgUserSetting($userId, true);
        }

        $this->getEntityManager()->persist($ngUserSetting);
        $this->getEntityManager()->flush();

        return $ngUserSetting;
    }
}
