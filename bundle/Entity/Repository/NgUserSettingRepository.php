<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Netgen\Bundle\SiteBundle\Entity\NgUserSetting;

class NgUserSettingRepository extends EntityRepository
{
    /**
     * Returns if user specified by $userId is activated.
     *
     * @param mixed $userId
     */
    public function isUserActivated($userId): bool
    {
        $ngUserSetting = $this->findOneBy(['userId' => $userId]);

        if ($ngUserSetting instanceof NgUserSetting) {
            return $ngUserSetting->getIsActivated();
        }

        return false;
    }

    /**
     * Activates the user specified by $userId.
     *
     * @param mixed $userId
     */
    public function activateUser($userId): NgUserSetting
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
