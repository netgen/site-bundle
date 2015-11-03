<?php

namespace Netgen\Bundle\MoreBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Netgen\Bundle\MoreBundle\Entity\EzUserAccountKey;

class EzUserAccountKeyRepository extends EntityRepository
{
    /**
     * Creates a user account key
     *
     * @param mixed $userId
     *
     * @return \Netgen\Bundle\MoreBundle\Entity\EzUserAccountKey
     */
    public function create( $userId )
    {
        $this->removeByUserId( $userId );

        $hash = md5(
            $userId . ':' . microtime() . ':' .
            ( function_exists( "openssl_random_pseudo_bytes" ) ?
                openssl_random_pseudo_bytes( 32 ) : mt_rand() )
        );

        $userAccount = new EzUserAccountKey();
        $userAccount->setHash( $hash );
        $userAccount->setTime( time() );
        $userAccount->setUserId( $userId );

        $this->getEntityManager()->persist( $userAccount );
        $this->getEntityManager()->flush();

        return $userAccount;
    }

    /**
     * Returns user account key by hash
     *
     * @param string $hash
     *
     * @return \Netgen\Bundle\MoreBundle\Entity\EzUserAccountKey
     */
    public function getByHash( $hash )
    {
        return $this->findOneBy( array( 'hashKey' => $hash ) );
    }

    /**
     * Removes user account key for user specified by $userId
     *
     * @param mixed $userId
     */
    public function removeByUserId( $userId )
    {
        $results = $this->findBy( array( 'userId' => $userId ) );

        foreach ( $results as $result )
        {
            $this->getEntityManager()->remove( $result );
        }

        $this->getEntityManager()->flush();
    }

    /**
     * Removes user account key by user hash
     *
     * @param string $hash
     */
    public function removeByHash( $hash )
    {
        $results = $this->findBy( array( 'hashKey' => $hash ) );

        foreach ( $results as $result )
        {
            $this->getEntityManager()->remove( $result );
        }

        $this->getEntityManager()->flush();
    }
}
