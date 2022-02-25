<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Menu\Factory\LocationFactory;

use Ibexa\Core\FieldType\Url\Value as UrlValue;
use Ibexa\Core\MVC\Symfony\SiteAccess\URILexer;
use Knp\Menu\ItemInterface;
use Netgen\IbexaSiteApi\API\LoadService;
use Netgen\IbexaSiteApi\API\Values\Content;
use Netgen\IbexaSiteApi\API\Values\Location;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use function mb_stripos;
use function sprintf;

class ShortcutExtension implements ExtensionInterface
{
    protected LoadService $loadService;

    protected UrlGeneratorInterface $urlGenerator;

    protected RequestStack $requestStack;

    protected LoggerInterface $logger;

    public function __construct(
        LoadService $loadService,
        UrlGeneratorInterface $urlGenerator,
        RequestStack $requestStack,
        ?LoggerInterface $logger = null
    ) {
        $this->loadService = $loadService;
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
        $this->logger = $logger ?? new NullLogger();
    }

    public function matches(Location $location): bool
    {
        return $location->contentInfo->contentTypeIdentifier === 'ng_shortcut';
    }

    public function buildItem(ItemInterface $item, Location $location): void
    {
        $this->buildItemFromContent($item, $location->content);

        if ($location->content->getField('target_blank')->value->bool) {
            $item->setLinkAttribute('target', '_blank')
                ->setLinkAttribute('rel', 'nofollow noopener noreferrer');
        }
    }

    protected function buildItemFromContent(ItemInterface $item, Content $content): void
    {
        if (!$content->getField('url')->isEmpty()) {
            $this->buildItemFromUrl($item, $content->getField('url')->value, $content);

            return;
        }

        $relatedContent = null;
        if (!$content->getField('related_object')->isEmpty()) {
            $relatedContent = $content->getFieldRelation('related_object');
        }

        if (!$relatedContent instanceof Content) {
            return;
        }

        if (!$relatedContent->mainLocation->isVisible) {
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

            if (!$content->getField('use_shortcut_name')->value->bool) {
                $item->setLabel($urlValue->text);
            }
        }
    }

    protected function buildItemFromRelatedContent(ItemInterface $item, Content $content, Content $relatedContent): void
    {
        $contentUri = $this->urlGenerator->generate('', [RouteObjectInterface::ROUTE_OBJECT => $relatedContent]);
        $item->setUri($contentUri . $content->getField('internal_url_suffix')->value->text)
            ->setExtra('ibexa_location', $relatedContent->mainLocation)
            ->setAttribute('id', 'menu-item-location-id-' . $relatedContent->mainLocationId)
            ->setLinkAttribute('title', $item->getLabel());

        if (!$content->getField('use_shortcut_name')->value->bool) {
            $item->setLabel($relatedContent->name);
        }
    }
}
