<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Entity;

class NgUserSetting
{
    /**
     * @var mixed
     */
    protected $userId;

    protected bool $isActivated;

    /**
     * @param mixed $userId
     */
    public function __construct($userId, bool $isActivated)
    {
        $this->userId = $userId;
        $this->isActivated = $isActivated;
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
