<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Menu\Factory\LocationFactory;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\FieldType\Url\Value as UrlValue;
use Ibexa\Core\MVC\Symfony\SiteAccess\URILexer;
use Knp\Menu\ItemInterface;
use Netgen\IbexaSiteApi\API\Values\Content;
use Netgen\IbexaSiteApi\API\Values\Location;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function in_array;
use function sprintf;
use function str_starts_with;

final class MenuItemExtension implements ExtensionInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private RequestStack $requestStack,
        private ConfigResolverInterface $configResolver,
        private ChildrenBuilder $childrenBuilder,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function matches(Location $location): bool
    {
        return $location->contentInfo->contentTypeIdentifier === 'ng_menu_item';
    }

    public function buildItem(ItemInterface $item, Location $location): void
    {
        $this->buildItemFromContent($item, $location->content);

        if ($location->content->getField('target_blank')->value->bool) {
            $item->setLinkAttribute('target', '_blank')
                ->setLinkAttribute('rel', 'nofollow noopener noreferrer');
        }

        $this->childrenBuilder->buildChildItems($item, $location->content);
    }

    private function buildItemFromContent(ItemInterface $item, Content $content): void
    {
        if (!$content->getField('item_url')->isEmpty()) {
            /** @var \Ibexa\Core\FieldType\Url\Value $urlValue */
            $urlValue = $content->getField('item_url')->value;
            $this->buildItemFromUrl($item, $urlValue, $content);

            return;
        }

        $relatedContent = null;
        if (!$content->getField('item_object')->isEmpty()) {
            $relatedContent = $content->getFieldRelation('item_object');
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

    private function buildItemFromUrl(ItemInterface $item, UrlValue $urlValue, Content $content): void
    {
        $uri = $urlValue->link ?? '';

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

        if (($urlValue->text ?? '') !== '') {
            $item->setLinkAttribute('title', $urlValue->text);

            if (!$content->getField('use_menu_item_name')->value->bool) {
                $item->setLabel($urlValue->text);
            }
        }
    }

    private function buildItemFromRelatedContent(ItemInterface $item, Content $content, Content $relatedContent): void
    {
        $item
            ->setUri($this->urlGenerator->generate('', [RouteObjectInterface::ROUTE_OBJECT => $relatedContent]))
            ->setExtra('ibexa_location', $relatedContent->mainLocation)
            ->setAttribute('id', 'menu-item-' . $item->getExtra('menu_name') . '-location-id-' . $relatedContent->mainLocationId)
            ->setLinkAttribute('title', $item->getLabel());

        if (!$content->getField('use_menu_item_name')->value->bool) {
            $item->setLabel($relatedContent->name);
        }

        $containerClasses = $this->configResolver->getParameter('container_content_types', 'ngsite');
        if (in_array($relatedContent->contentInfo->contentTypeIdentifier, $containerClasses, true)) {
            // Disable link for content types that act as simple content containers
            // and that have no their own full views
            $item->setUri('');
        }
    }
}
