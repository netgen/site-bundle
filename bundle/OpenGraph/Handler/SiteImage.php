<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\OpenGraph\Handler;

use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use eZ\Publish\Core\FieldType\Image\Value as ImageValue;
use Netgen\Bundle\OpenGraphBundle\Handler\HandlerInterface;
use Netgen\Bundle\OpenGraphBundle\MetaTag\Item;
use Netgen\Bundle\SiteBundle\Helper\SiteInfoHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;

class SiteImage implements HandlerInterface
{
    /**
     * Field identifier that provides opengraph image
     *
     * @var string
     */
    protected const FIELD_IDENTIFIER = 'site_opengraph_image';

    /**
     * @var \Netgen\Bundle\SiteBundle\Helper\SiteInfoHelper
     */
    protected $siteInfoHelper;

    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    private $requestStack;

    /**
     * SiteImage constructor.
     *
     * @param \Netgen\Bundle\SiteBundle\Helper\SiteInfoHelper $siteInfoHelper
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     */
    public function __construct(SiteInfoHelper $siteInfoHelper, RequestStack $requestStack)
    {
        $this->siteInfoHelper = $siteInfoHelper;
        $this->requestStack = $requestStack;
    }

    /**
     * @inheritdoc
     */
    public function getMetaTags($tagName, array $params = []): array
    {
        $siteInfoContent = $this->siteInfoHelper->getSiteInfoContent();

        if (
            $siteInfoContent->hasField(self::FIELD_IDENTIFIER)
            && !$siteInfoContent->getField(self::FIELD_IDENTIFIER)->isEmpty()
            && $siteInfoContent->getFieldValue(self::FIELD_IDENTIFIER) instanceof ImageValue
        ) {
            $siteImage = $this->siteInfoHelper
                ->getSiteInfoContent()
                ->getField(self::FIELD_IDENTIFIER)
                ->value
                ->uri;
        } elseif (!empty($params[0])) {
            $siteImage = (string) $params[0];
        } else {
            throw new InvalidArgumentException(
                sprintf('%s or $params[0]', self::FIELD_IDENTIFIER),
                'Either field does not provide valid value or fallback is not properly set up.'
            );
        }

        $request = $this->requestStack->getCurrentRequest();

        if (!preg_match('/^https?:\/\//', $siteImage) && $request instanceof Request) {
            $siteImage = $request->getUriForPath('/' . ltrim($siteImage, '/'));
        }

        return [
            new Item($tagName, $siteImage),
        ];
    }
}
