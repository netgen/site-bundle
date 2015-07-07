<?php

namespace Netgen\Bundle\MoreBundle\Entity;

class EzUserAccount
{
    /**
     * @var string
     */
    private $hash;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $time;

    /**
     * @var int
     */
    private $user_id;

    /**
     * Set hash key
     *
     * @param string $hash
     *
     * @return EzUserAccount
     */
    public function setHash( $hash )
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get hash key
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Set user ID
     *
     * @param int $user_id
     *
     * @return EzUserAccount
     */
    public function setUserId( $user_id )
    {
        $this->user_id = $user_id;

        return $this;
    }

    /**
     * Get user ID
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set time
     *
     * @param string $timestamp
     *
     * @return EzUserAccount
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

    public function getId()
    {
        return $this->id;
    }
}