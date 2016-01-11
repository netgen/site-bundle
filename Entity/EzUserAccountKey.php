<?php

namespace Netgen\Bundle\MoreBundle\Entity;

class EzUserAccountKey
{
    /**
     * @var mixed
     */
    protected $id;

    /**
     * @var string
     */
    protected $hashKey;

    /**
     * @var int
     */
    protected $time;

    /**
     * @var mixed
     */
    protected $userId;

    /**
     * Get ID.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set hash key.
     *
     * @param string $hashKey
     *
     * @return \Netgen\Bundle\MoreBundle\Entity\EzUserAccountKey
     */
    public function setHash($hashKey)
    {
        $this->hashKey = $hashKey;

        return $this;
    }

    /**
     * Get hash key.
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hashKey;
    }

    /**
     * Set user ID.
     *
     * @param mixed $userId
     *
     * @return \Netgen\Bundle\MoreBundle\Entity\EzUserAccountKey
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get user ID.
     *
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set time.
     *
     * @param int $time
     *
     * @return \Netgen\Bundle\MoreBundle\Entity\EzUserAccountKey
     */
    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Get time.
     *
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }
}
