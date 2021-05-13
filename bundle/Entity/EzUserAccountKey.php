<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Entity;

class EzUserAccountKey
{
    /**
     * @var mixed
     */
    protected $id;

    protected string $hashKey;

    protected int $time;

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
     */
    public function setHash(string $hashKey): self
    {
        $this->hashKey = $hashKey;

        return $this;
    }

    /**
     * Get hash key.
     */
    public function getHash(): string
    {
        return $this->hashKey;
    }

    /**
     * Set user ID.
     *
     * @param mixed $userId
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
     */
    public function setTime(int $time): self
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Get time.
     */
    public function getTime(): int
    {
        return $this->time;
    }
}
