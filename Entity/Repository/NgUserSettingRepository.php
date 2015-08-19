<?php

namespace Netgen\Bundle\MoreBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Netgen\Bundle\MoreBundle\Entity\NgUserSetting;

class NgUserSettingRepository extends EntityRepository
{
    /**
     * Creates and stores to db new NgUserSetting (not activated by default)
     *
     * @param int   $userId
     * @param bool  $isActivated
     *
     * @return \Netgen\Bundle\MoreBundle\Entity\NgUserSetting
     */
    public function createNgUserSetting( $userId, $isActivated = false )
    {
        $ngUserSetting = new NgUserSetting( $userId, $isActivated );
        $this->getEntityManager()->persist( $ngUserSetting );
        $this->getEntityManager()->flush();

        return $ngUserSetting;
    }

    /**
     * Returns NgUserSetting for userId
     *
     * @param int   $userId
     *
     * @return \Netgen\Bundle\MoreBundle\Entity\NgUserSetting|null
     */
    public function getByUserId( $userId )
    {
        $ngUserSetting = $this->findOneBy( array( 'userId' => $userId ) );

        if ( $ngUserSetting instanceof NgUserSetting )
        {
            return $ngUserSetting;
        }

        return null;
    }

    public function isUserIdActivated( $userId )
    {
        $ngUserSetting = $this->getByUserId( $userId );

        if ( $ngUserSetting )
        {
            return $ngUserSetting->getIsActivated();
        }

        throw new \InvalidArgumentException();
    }

    public function activateUserId( $userId )
    {
        $ngUserSetting = $this->findOneBy( array( 'userId' => $userId ) );

        $ngUserSetting->setIsActivated( true );

        $this->getEntityManager()->persist( $ngUserSetting );
        $this->getEntityManager()->flush();

        return $ngUserSetting;
    }
}