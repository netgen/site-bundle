<?php

namespace Netgen\Bundle\MoreBundle\Event\Content;

use Symfony\Component\EventDispatcher\Event;

class DownloadEvent extends Event
{
    /**
     * @var integer
     */
    protected $contentId;

    /**
     * @var string
     */
    protected $fieldId;

    /**
     * @var integer
     */
    protected $versionNo;

    /**
     * DownloadEvent constructor.
     *
     * @param integer $contentId
     * @param integer $fieldId
     * @param integer $versionNo
     */
    public function __construct($contentId, $fieldId, $versionNo)
    {
        $this->contentId = $contentId;
        $this->fieldId = $fieldId;
        $this->versionNo = $versionNo;
    }

    /**
     * Get field ID
     *
     * @return string
     */
    public function getFieldId()
    {
        return $this->fieldId;
    }
    
    /**
     * Returns contentId
     *
     * @return int
     */
    public function getContentId()
    {
        return $this->contentId;
    }

    /**
     * Returns version number
     *
     * @return int
     */
    public function getVersionNo()
    {
        return $this->versionNo;
    }
}