<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Entity;

class NgUserSetting
{
    /**
     * @var mixed
     */
    protected $userId;

    /**
     * @var bool
     */
    protected $isActivated;

    /**
     * Constructor.
     *
     * @param mixed $userId
     * @param bool $isActivated
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
     *
     * @return \Netgen\Bundle\SiteBundle\Entity\NgUserSetting
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
     *
     * @param bool $isActivated
     *
     * @return \Netgen\Bundle\SiteBundle\Entity\NgUserSetting
     */
    public function setIsActivated(bool $isActivated): self
    {
        $this->isActivated = $isActivated;

        return $this;
    }

    /**
     * Returns true once the user has been first activated, false before that.
     *
     * @return bool
     */
    public function getIsActivated(): bool
    {
        return $this->isActivated;
    }
}
