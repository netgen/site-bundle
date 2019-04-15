<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\OpenGraph\Handler;

use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use eZ\Publish\Core\FieldType\Image\Value as ImageValue;
use Netgen\Bundle\OpenGraphBundle\Handler\HandlerInterface;
use Netgen\Bundle\OpenGraphBundle\MetaTag\Item;
use Netgen\Bundle\SiteBundle\Helper\SiteInfoHelper;

class SiteImage implements HandlerInterface
{
    /**
     * Field identifier that provides opengraph image
     *
     * @var string
     */
    protected $fieldDefinitionIdentifier = 'site_opengraph_image';

    /**
     * @var \Netgen\Bundle\SiteBundle\Helper\SiteInfoHelper
     */
    protected $siteInfoHelper;

    /**
     * SiteImage constructor.
     *
     * @param \Netgen\Bundle\SiteBundle\Helper\SiteInfoHelper $siteInfoHelper
     */
    public function __construct(SiteInfoHelper $siteInfoHelper)
    {
        $this->siteInfoHelper = $siteInfoHelper;
    }

    /**
     * @inheritdoc
     */
    public function getMetaTags($tagName, array $params = []): array
    {
        $siteInfoContent = $this->siteInfoHelper->getSiteInfoContent();

        if (
            $siteInfoContent->hasField($this->fieldDefinitionIdentifier)
            && $siteInfoContent->getField($this->fieldDefinitionIdentifier)->isEmpty()
            && $siteInfoContent->getFieldValue($this->fieldDefinitionIdentifier) instanceof ImageValue
        ) {

            $siteImage = $this->siteInfoHelper
                ->getSiteInfoContent()
                ->getField($this->fieldDefinitionIdentifier)
                ->value
                ->uri;

        } elseif (!empty($params[0])) {

            $siteImage = (string) $params[0];

        } else {

            throw new InvalidArgumentException(
                sprintf('%s or $params[0]', $this->fieldDefinitionIdentifier),
                'Either field does not provide valid value or fallback is not properly set up.'
            );

        }

        return [
            new Item($tagName, trim($siteImage)),
        ];
    }
}
