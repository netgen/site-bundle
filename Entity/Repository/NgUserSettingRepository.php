<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Netgen\Bundle\MoreBundle\Entity\NgUserSetting;

class NgUserSettingRepository extends EntityRepository
{
    /**
     * Returns if user specified by $userId is activated.
     *
     * @param mixed $userId
     *
     * @return bool
     */
    public function isUserActivated($userId)
    {
        $ngUserSetting = $this->findOneBy(array('userId' => $userId));

        if ($ngUserSetting instanceof NgUserSetting) {
            return $ngUserSetting->getIsActivated();
        }

        return false;
    }

    /**
     * Activates the user specified by $userId.
     *
     * @param mixed $userId
     *
     * @return \Netgen\Bundle\MoreBundle\Entity\NgUserSetting
     */
    public function activateUser($userId)
    {
        $ngUserSetting = $this->findOneBy(array('userId' => $userId));

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
