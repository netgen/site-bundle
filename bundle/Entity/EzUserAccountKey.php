<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Entity;

class EzUserAccountKey
{
    /**
     * @var int|string
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
     * @var int
     */
    protected $userId;

    /**
     * Get ID.
     *
     * @return int|string
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
     * @param int $userId
     *
     * @return \Netgen\Bundle\SiteBundle\Entity\EzUserAccountKey
     */
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get user ID.
     *
     * @return int
     */
    public function getUserId(): int
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
