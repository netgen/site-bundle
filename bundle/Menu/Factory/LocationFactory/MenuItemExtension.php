<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Menu\Factory\LocationFactory;

use eZ\Publish\Core\FieldType\Url\Value as UrlValue;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\MVC\Symfony\SiteAccess\URILexer;
use Knp\Menu\ItemInterface;
use Netgen\EzPlatformSiteApi\API\Values\Content;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function in_array;
use function mb_stripos;
use function sprintf;

class MenuItemExtension implements ExtensionInterface
{
    protected UrlGeneratorInterface $urlGenerator;

    protected RequestStack $requestStack;

    protected ConfigResolverInterface $configResolver;

    protected ChildrenBuilder $childrenBuilder;

    protected LoggerInterface $logger;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        RequestStack $requestStack,
        ConfigResolverInterface $configResolver,
        ChildrenBuilder $childrenBuilder,
        ?LoggerInterface $logger = null
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
        $this->configResolver = $configResolver;
        $this->childrenBuilder = $childrenBuilder;
        $this->logger = $logger ?? new NullLogger();
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

    protected function buildItemFromContent(ItemInterface $item, Content $content): void
    {
        if (!$content->getField('item_url')->isEmpty()) {
            $this->buildItemFromUrl($item, $content->getField('item_url')->value, $content);

            return;
        }

        $relatedContent = null;
        if (!$content->getField('item_object')->isEmpty()) {
            $relatedContent = $content->getFieldRelation('item_object');
        }

        if (!$relatedContent instanceof Content) {
            return;
        }

        if ($relatedContent->mainLocation->invisible) {
            $this->logger->error(sprintf('Menu item (#%s) has a related object (#%s) that is not visible.', $content->id, $relatedContent->id));

            return;
        }

        $this->buildItemFromRelatedContent($item, $content, $relatedContent);
    }

    protected function buildItemFromUrl(ItemInterface $item, UrlValue $urlValue, Content $content): void
    {
        $uri = $urlValue->link;

        if (mb_stripos($urlValue->link, 'http') !== 0) {
            $currentSiteAccess = $this->requestStack->getMasterRequest()->attributes->get('siteaccess');
            if ($currentSiteAccess->matcher instanceof URILexer) {
                $uri = $currentSiteAccess->matcher->analyseLink($uri);
            }
        }

        $item->setUri($uri);

        if (!empty($urlValue->text)) {
            $item->setLinkAttribute('title', $urlValue->text);

            if (!$content->getField('use_menu_item_name')->value->bool) {
                $item->setLabel($urlValue->text);
            }
        }
    }

    protected function buildItemFromRelatedContent(ItemInterface $item, Content $content, Content $relatedContent): void
    {
        $menuName = $item->getExtra('menu_name');

        $item
            ->setUri($this->urlGenerator->generate($relatedContent))
            ->setExtra('ezlocation', $relatedContent->mainLocation)
            ->setAttribute('id', 'menu-item-' . ($menuName ? $menuName . '-' : '') . 'location-id-' . $relatedContent->mainLocationId)
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
