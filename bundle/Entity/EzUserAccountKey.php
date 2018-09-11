<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Entity;

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
     * @return \Netgen\Bundle\SiteBundle\Entity\EzUserAccountKey
     */
    public function setHash(string $hashKey): self
    {
        $this->hashKey = $hashKey;

        return $this;
    }

    /**
     * Get hash key.
     *
     * @return string
     */
    public function getHash(): string
    {
        return $this->hashKey;
    }

    /**
     * Set user ID.
     *
     * @param mixed $userId
     *
     * @return \Netgen\Bundle\SiteBundle\Entity\EzUserAccountKey
     */
    public function setUserId($userId): self
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
     * @return \Netgen\Bundle\SiteBundle\Entity\EzUserAccountKey
     */
    public function setTime(int $time): self
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Get time.
     *
     * @return int
     */
    public function getTime(): int
    {
        return $this->time;
    }
}
