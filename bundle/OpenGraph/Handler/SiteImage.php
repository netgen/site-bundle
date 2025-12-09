<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\OpenGraph\Handler;

use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\Image\Value as ImageValue;
use Netgen\Bundle\IbexaSiteApiBundle\NamedObject\Provider;
use Netgen\Bundle\OpenGraphBundle\Handler\HandlerInterface;
use Netgen\Bundle\OpenGraphBundle\MetaTag\Item;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use function mb_ltrim;
use function preg_match;
use function sprintf;

final class SiteImage implements HandlerInterface
{
    /**
     * Field identifier that provides opengraph image.
     */
    private const FIELD_IDENTIFIER = 'site_opengraph_image';

    public function __construct(
        private Provider $namedObjectProvider,
        private RequestStack $requestStack,
    ) {}

    /**
     * @param mixed[] $params
     */
    public function getMetaTags(string $tagName, array $params = []): array
    {
        $siteInfoContent = $this->namedObjectProvider->getLocation('site_info')->content;

        if (
            $siteInfoContent->hasField(self::FIELD_IDENTIFIER)
            && !$siteInfoContent->getField(self::FIELD_IDENTIFIER)->isEmpty()
            && $siteInfoContent->getFieldValue(self::FIELD_IDENTIFIER) instanceof ImageValue
        ) {
            $siteImage = $siteInfoContent
                ->getField(self::FIELD_IDENTIFIER)
                ->value
                ->uri;
        } elseif (($params[0] ?? '') !== '') {
            $siteImage = $params[0];
        } else {
            throw new InvalidArgumentException(
                sprintf('%s or $params[0]', self::FIELD_IDENTIFIER),
                'Either field does not provide valid value or fallback is not properly set up.',
            );
        }

        $request = $this->requestStack->getCurrentRequest();

        if ($request instanceof Request && preg_match('/^https?:\/\//', $siteImage) !== 1) {
            $siteImage = $request->getUriForPath('/' . mb_ltrim($siteImage, '/'));
        }

        return [
            new Item($tagName, $siteImage),
        ];
    }
}
