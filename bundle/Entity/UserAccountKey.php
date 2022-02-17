<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Entity;

class UserAccountKey
{
    /**
     * @var int|string
     */
    protected $id;

    protected string $hashKey;

    protected int $time;

    protected int $userId;

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
     */
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get user ID.
     */
    public function getUserId(): int
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
