<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\RichText\Converter;

use DOMDocument;
use DOMXPath;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException as APINotFoundException;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Contracts\FieldTypeRichText\RichText\Converter;
use Netgen\IbexaSiteApi\API\LoadService;
use Netgen\IbexaSiteApi\API\Values\Content;
use Netgen\IbexaSiteApi\API\Values\Location;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\RouterInterface;

use function in_array;
use function preg_match;
use function sprintf;

final class Link implements Converter
{
    private const DOWNLOAD_LINK_INLINE = 1;

    public function __construct(
        private readonly Repository $repository,
        private readonly LoadService $loadService,
        private readonly RouterInterface $router,
        private readonly ConfigResolverInterface $configResolver,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function convert(DOMDocument $xmlDoc): DOMDocument
    {
        $document = clone $xmlDoc;

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('docbook', 'http://docbook.org/ns/docbook');
        $xpath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');

        $linkAttributeExpression = "starts-with( @xlink:href, 'ezlocation://' ) or starts-with( @xlink:href, 'ezcontent://' )";
        $xpathExpression = sprintf('//docbook:link[%s]|//docbook:ezlink', $linkAttributeExpression);

        /**
         * @var \DOMElement $link
         *
         * @phpstan-ignore-next-line
         */
        foreach ($xpath->query($xpathExpression) as $link) {
            // Set resolved href to number character as a default if it can't be resolved
            $hrefResolved = '#';
            $href = $link->getAttribute('xlink:href');
            preg_match('~^(.+://)?([^#]*)?(#.*|\\s*)?$~', $href, $matches);
            [, $scheme, $id, $fragment] = $matches;

            if ($scheme === 'ezcontent://') {
                try {
                    $content = $this->loadContent((int) $id);
                    $hrefResolved = $this->generateUrlAliasForContentOrLocation(
                        $content,
                        $fragment,
                    );

                    $downloadLink = $this->getDownloadLink($content);
                    $hrefResolved = $downloadLink ?? $hrefResolved;
                } catch (APINotFoundException) {
                    $this->logger->error(
                        sprintf('While generating link for RichText, could not find Content #%s', $id),
                    );
                }
            } elseif ($scheme === 'ezlocation://') {
                try {
                    $location = $this->loadLocation((int) $id);
                    $hrefResolved = $this->generateUrlAliasForContentOrLocation(
                        $location,
                        $fragment,
                    );

                    $content = $location->content;
                    $downloadLink = $this->getDownloadLink($content);
                    $hrefResolved = $downloadLink ?? $hrefResolved;
                } catch (APINotFoundException) {
                    $this->logger->error(
                        sprintf('While generating link for RichText, could not find Location #%s', $id),
                    );
                }
            } else {
                $hrefResolved = $href;
            }

            $hrefAttributeName = 'xlink:href';

            // For embeds set the resolved href to the separate attribute
            // Original href needs to be preserved in order to generate link parameters
            // This will need to change with introduction of UrlService and removal of URL link
            // resolving in external storage
            if ($link->localName === 'ezlink') {
                $hrefAttributeName = 'href_resolved';
            }

            $link->setAttribute($hrefAttributeName, $hrefResolved);
        }

        return $document;
    }

    private function getDownloadLink(Content $content): ?string
    {
        $contentTypeIdentifier = $content->contentInfo->contentTypeIdentifier;
        $downloadableContentTypes = $this->configResolver->getParameter('direct_download.content_types', 'ngsite');

        if (!in_array($contentTypeIdentifier, $downloadableContentTypes, true)
            || !$content->hasField('file')
            || $content->getField('file')->isEmpty()
        ) {
            return null;
        }

        return $this->router->generate('ngsite_download', [
            'contentId' => $content->id,
            'fieldId' => $content->getField('file')->id,
            'isInline' => self::DOWNLOAD_LINK_INLINE,
        ]);
    }

    private function loadContent(int $id): Content
    {
        return $this->repository->sudo(
            fn (): Content => $this->loadService->loadContent($id),
        );
    }

    private function loadLocation(int $id): Location
    {
        return $this->repository->sudo(
            fn (): Location => $this->loadService->loadLocation($id),
        );
    }

    private function generateUrlAliasForContentOrLocation(Content|Location $object, string $fragment): string
    {
        $urlAlias = $this->router->generate(
            RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
            [
                RouteObjectInterface::ROUTE_OBJECT => $object,
            ],
        );

        return $urlAlias . $fragment;
    }
}
