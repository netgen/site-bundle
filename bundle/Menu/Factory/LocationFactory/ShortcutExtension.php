<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Menu\Factory\LocationFactory;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Core\MVC\Symfony\SiteAccess\URILexer;
use Knp\Menu\ItemInterface;
use Netgen\IbexaSiteApi\API\LoadService;
use Netgen\IbexaSiteApi\API\Values\Content;
use Netgen\IbexaSiteApi\API\Values\Location;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function is_int;
use function is_string;
use function sprintf;
use function str_starts_with;

final class ShortcutExtension implements ExtensionInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private RequestStack $requestStack,
        private LoadService $loadService,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function matches(Location $location): bool
    {
        return $location->contentInfo->contentTypeIdentifier === 'ng_shortcut';
    }

    public function buildItem(ItemInterface $item, Location $location): void
    {
        /** @var \Netgen\IbexaFieldTypeEnhancedLink\FieldType\Value $link */
        $link = $location->content->getField('link')->value;

        $this->buildItemFromContent($item, $location->content);

        if ($link->isTargetLinkInNewTab()) {
            $item->setLinkAttribute('target', '_blank')
                ->setLinkAttribute('rel', 'nofollow noopener noreferrer');
        }
    }

    private function buildItemFromContent(ItemInterface $item, Content $content): void
    {
        /** @var \Netgen\IbexaFieldTypeEnhancedLink\FieldType\Value $link */
        $link = $content->getField('link')->value;

        if (is_string($link->reference) && $link->isTypeExternal()) {
            $this->buildItemFromUrl($item, $content);

            return;
        }

        $relatedContent = null;
        if (is_int($link->reference) && $link->reference > 0 && $link->isTypeInternal()) {
            try {
                $relatedContent = $this->loadService->loadContent($link->reference);
            } catch (NotFoundException|UnauthorizedException) {
                // Do nothing
            }
        }

        if (!$relatedContent instanceof Content || !$relatedContent->mainLocation instanceof Location) {
            return;
        }

        if (!$relatedContent->mainLocation->isVisible) {
            $this->logger->error(sprintf('Menu item (#%s) has a related object (#%s) that is not visible.', $content->id, $relatedContent->id));

            return;
        }

        $this->buildItemFromRelatedContent($item, $content, $relatedContent);
    }

    private function buildItemFromUrl(ItemInterface $item, Content $content): void
    {
        /** @var \Netgen\IbexaFieldTypeEnhancedLink\FieldType\Value $link */
        $link = $content->getField('link')->value;

        $uri = $link->reference ?? '';

        if (!str_starts_with($uri, 'http')) {
            $request = $this->requestStack->getMainRequest();
            if (!$request instanceof Request) {
                return;
            }

            $currentSiteAccess = $request->attributes->get('siteaccess');
            if ($currentSiteAccess->matcher instanceof URILexer) {
                $uri = $currentSiteAccess->matcher->analyseLink($uri);
            }
        }

        $item->setUri($uri);

        if (($link->label ?? '') !== '') {
            $item->setLinkAttribute('title', $link->label);

            if (!$content->getField('use_shortcut_name')->value->bool) {
                $item->setLabel($link->label);
            }
        }
    }

    private function buildItemFromRelatedContent(ItemInterface $item, Content $content, Content $relatedContent): void
    {
        /** @var \Netgen\IbexaFieldTypeEnhancedLink\FieldType\Value $link */
        $link = $content->getField('link')->value;

        if (!$content->getField('use_shortcut_name')->value->bool) {
            $item->setLabel($link->label ?? $relatedContent->name);
        }

        $contentUri = $this->urlGenerator->generate('', [RouteObjectInterface::ROUTE_OBJECT => $relatedContent]);
        $item->setUri($contentUri . ($link->suffix ?? ''))
            ->setExtra('ibexa_location', $relatedContent->mainLocation)
            ->setAttribute('id', 'menu-item-' . $item->getExtra('menu_name') . '-location-id-' . $relatedContent->mainLocationId)
            ->setLinkAttribute('title', $item->getLabel());
    }
}
