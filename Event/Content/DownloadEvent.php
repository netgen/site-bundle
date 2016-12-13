<?php

namespace Netgen\Bundle\MoreBundle\Event\Content;

use Symfony\Component\EventDispatcher\Event;

class DownloadEvent extends Event
{
    /**
     * @var int
     */
    protected $contentId;

    /**
     * @var int
     */
    protected $fieldId;

    /**
     * @var int
     */
    protected $versionNo;

    /**
     * Constructor.
     *
     * @param int $contentId
     * @param int $fieldId
     * @param int $versionNo
     */
    public function __construct($contentId, $fieldId, $versionNo)
    {
        $this->contentId = $contentId;
        $this->fieldId = $fieldId;
        $this->versionNo = $versionNo;
    }

    /**
     * Get field ID.
     *
     * @return int
     */
    public function getFieldId()
    {
        return $this->fieldId;
    }

    /**
     * Returns content ID.
     *
     * @return int
     */
    public function getContentId()
    {
        return $this->contentId;
    }

    /**
     * Returns version number.
     *
     * @return int
     */
    public function getVersionNo()
    {
        return $this->versionNo;
    }
}
