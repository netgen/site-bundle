<?php

namespace Netgen\Bundle\MoreBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Netgen\Bundle\MoreBundle\Entity\EzUserAccountKey;

class EzUserAccountKeyRepository extends EntityRepository
{
    /**
     * Creates verification hash key
     *
     * @param mixed $userId
     *
     * @return string|bool
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function setVerificationHash( $userId )
    {
        $this->removeEzUserAccountKeyByUserId( $userId );

        $hash = md5(
            ( function_exists( "openssl_random_pseudo_bytes" ) ? openssl_random_pseudo_bytes( 32 ) : mt_rand() ) .
            microtime() .
            $userId
        );

        $userAccount = new EzUserAccountKey();
        $userAccount->setHash( $hash );
        $userAccount->setTime( time() );
        $userAccount->setUserId( $userId );

        $this->getEntityManager()->persist( $userAccount );
        $this->getEntityManager()->flush();

        return $hash;
    }

    /**
     * Gets ezuser_accountkey by hash
     *
     * @param mixed $hash
     *
     * @return EzUserAccountKey|null
     */
    public function getEzUserAccountKeyByHash( $hash )
    {
        $result = $this->findOneBy(
            array(
                'hashKey' => $hash
            )
        );

        if ( $result instanceof EzUserAccountKey )
        {
            return $result;
        }

        return null;
    }

    /**
     * Removes all data for $userId from ezuser_accountkey table
     *
     * @param $userId
     */
    public function removeEzUserAccountKeyByUserId( $userId )
    {
        $results = $this->findBy(
            array(
                'userId' => $userId
            ),
            array(
                'time' => 'DESC'
            )
        );

        foreach( $results as $result )
        {
            $this->getEntityManager()->remove( $result );
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Removes hash key from ezuser_accountkey table
     *
     * @param $hash
     */
    public function removeEzUserAccountKeyByHash( $hash )
    {
        $results = $this->findBy(
            array(
                'hashKey' => $hash
            ),
            array(
                'time' => 'DESC'
            )
        );

        foreach( $results as $result )
        {
            $this->getEntityManager()->remove( $result );
            $this->getEntityManager()->flush();
        }
    }

    public function hashExists( $hash )
    {
        return $this->getEzUserAccountKeyByHash( $hash ) ? true : false;
    }
}