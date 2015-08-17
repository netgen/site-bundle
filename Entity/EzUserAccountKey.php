<?php

namespace Netgen\Bundle\MoreBundle\Entity;

class EzUserAccountKey
{
    /**
     * @var string
     */
    protected $hashKey;

    /**
     * @var mixed
     */
    protected $id;

    /**
     * @var int
     */
    protected $time;

    /**
     * @var mixed
     */
    protected $userId;

    /**
     * Set hash key
     *
     * @param string $hash
     *
     * @return \Netgen\Bundle\MoreBundle\Entity\EzUserAccountKey
     */
    public function setHash( $hash )
    {
        $this->hashKey = $hash;

        return $this;
    }

    /**
     * Get hash key
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hashKey;
    }

    /**
     * Set user ID
     *
     * @param int $userId
     *
     * @return \Netgen\Bundle\MoreBundle\Entity\EzUserAccountKey
     */
    public function setUserId( $userId )
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get user ID
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set time
     *
     * @param string $timestamp
     *
     * @return \Netgen\Bundle\MoreBundle\Entity\EzUserAccountKey
     */
    public function setTime( $timestamp )
    {
        $this->time = $timestamp;

        return $this;
    }

    /**
     * Get time
     *
     * @return string
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Returns EzUserAccountKey id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}