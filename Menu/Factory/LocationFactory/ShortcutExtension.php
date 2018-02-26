<?php

namespace Netgen\Bundle\MoreBundle\Menu\Factory\LocationFactory;

use eZ\Publish\Core\FieldType\Url\Value as UrlValue;
use InvalidArgumentException;
use Knp\Menu\ItemInterface;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Netgen\EzPlatformSiteApi\API\Values\Content;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;

class ShortcutExtension implements ExtensionInterface
{
    /**
     * @var \Netgen\EzPlatformSiteApi\API\LoadService
     */
    protected $loadService;

    /**
     * @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * @var \Psr\Log\NullLogger
     */
    protected $logger;

    public function __construct(LoadService $loadService, UrlGeneratorInterface $urlGenerator, LoggerInterface $logger = null)
    {
        $this->loadService = $loadService;
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger ?: new NullLogger();
    }

    public function matches(Location $location)
    {
        return $location->contentInfo->contentTypeIdentifier === 'ng_shortcut';
    }

    public function buildItem(ItemInterface $item, Location $location)
    {
        $item->setName($location->content->name);
        $item->setLabel($location->content->name);

        $this->buildItemFromContent($item, $location->content);

        if (!empty($item->getUri()) && $location->content->getField('target_blank')->value->bool) {
            $item->setLinkAttribute('target', '_blank');
            $item->setLinkAttribute('rel', 'noopener noreferrer');
        }
    }

    protected function buildItemFromContent(ItemInterface $item, Content $content)
    {
        if (!$content->getField('url')->isEmpty()) {
            $this->buildItemFromUrl($item, $content->getField('url')->value, $content);

            return;
        }

        if ($content->getField('related_object')->isEmpty()) {
            return;
        }

        try {
            $relatedContent = $this->loadService->loadContent(
                $content->getField('related_object')->value->destinationContentId
            );
        } catch (Throwable $t) {
            $this->logger->error($t->getMessage());

            return;
        }

        if (!$relatedContent->contentInfo->published) {
            $this->logger->error(sprintf('Menu item (#%s) has a related object (#%s) that is not published.', $content->id, $relatedContent->id));

            return;
        }

        if ($relatedContent->mainLocation->invisible) {
            $this->logger->error(sprintf('Menu item (#%s) has a related object (#%s) that is not visible.', $content->id, $relatedContent->id));

            return;
        }

        $this->buildItemFromRelatedContent($item, $content, $relatedContent);
    }

    protected function buildItemFromUrl(ItemInterface $item, UrlValue $urlValue, Content $content)
    {
        $uri = $urlValue->link;

        if (stripos($urlValue->link, 'http') !== 0) {
            try {
                $uri = $this->urlGenerator->generate(
                    'ez_legacy',
                    array(
                        'module_uri' => $urlValue->link,
                    )
                );
            } catch (InvalidArgumentException $e) {
                // Do nothing
            }
        }

        $item->setUri($uri);
        $item->setName($uri);

        if (!empty($urlValue->text)) {
            $item->setLinkAttribute('title', $urlValue->text);

            if (!$content->getField('use_shortcut_name')->value->bool) {
                $item->setLabel($urlValue->text);
            }
        }
    }

    protected function buildItemFromRelatedContent(ItemInterface $item, Content $content, Content $relatedContent)
    {
        $item->setUri($this->urlGenerator->generate($relatedContent) . $content->getField('internal_url_suffix')->value->text);
        $item->setName($relatedContent->mainLocationId);
        $item->setExtra('ezlocation', $relatedContent->mainLocation);
        $item->setAttribute('id', 'menu-item-location-id-' . $relatedContent->mainLocationId);
        $item->setLinkAttribute('title', $item->getLabel());

        if (!$content->getField('use_shortcut_name')->value->bool) {
            $item->setLabel($relatedContent->name);
        }
    }
}
