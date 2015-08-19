<?php

namespace Netgen\Bundle\MoreBundle\Entity;

class NgUserSetting
{
    /**
     * @var mixed
     */
    protected $userId;

    /**
     * @var int
     */
    protected $isActivated;

    /**
     * Sets user id
     *
     * @param $userId
     *
     * @return \Netgen\Bundle\MoreBundle\Entity\NgUserSetting
     */
    public function setUserId( $userId )
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Returns user id
     *
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Sets isActivated
     *
     * @param $isActivated
     *
     * @return \Netgen\Bundle\MoreBundle\Entity\NgUserSetting
     */
    public function setIsActivated( $isActivated )
    {
        $this->isActivated = (bool)$isActivated;

        return $this;
    }

    /**
     * Returns isActivated (true once the user has been first activated, false before that)
     *
     * @return int
     */
    public function getIsActivated()
    {
        return $this->isActivated;
    }

}