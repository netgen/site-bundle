<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Entity;

class NgUserSetting
{
    protected int $userId;

    protected bool $isActivated;

    public function __construct(int $userId, bool $isActivated)
    {
        $this->userId = $userId;
        $this->isActivated = $isActivated;
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
     * Sets if user has been activated at least once.
     */
    public function setIsActivated(bool $isActivated): self
    {
        $this->isActivated = $isActivated;

        return $this;
    }

    /**
     * Returns true once the user has been first activated, false before that.
     */
    public function getIsActivated(): bool
    {
        return $this->isActivated;
    }
}
