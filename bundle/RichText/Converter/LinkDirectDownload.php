<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\RichText\Converter;

use DOMDocument;
use DOMXPath;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException as APINotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException as APIUnauthorizedException;
use Ibexa\Contracts\FieldTypeRichText\RichText\Converter;
use Netgen\IbexaSiteApi\API\LoadService;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use function preg_match;

class LinkDirectDownload implements Converter
{
    protected LoadService $loadService;

    protected UrlGeneratorInterface $urlGenerator;

    protected LoggerInterface $logger;

    public function __construct(
        LoadService $loadService,
        UrlGeneratorInterface $urlGenerator,
        ?LoggerInterface $logger = null
    ) {
        $this->loadService = $loadService;
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Converts internal links (ezcontent:// and ezlocation://) to URLs.
     *
     * Overridden to add option to download files by using Netgen Site specific route.
     */
    public function convert(DOMDocument $xmlDoc): DOMDocument
    {
        $xmlDoc = clone $xmlDoc;

        $xpath = new DOMXPath($xmlDoc);
        $xpath->registerNamespace('docbook', 'http://docbook.org/ns/docbook');
        $xpath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');

        $linkAttributeExpression = "starts-with( @xlink:href, 'ezlocation://' ) or starts-with( @xlink:href, 'ezcontent://' )";
        $xpathExpression = "//docbook:link[{$linkAttributeExpression}]|//docbook:ezlink";

        /** @var \DOMElement $link */
        foreach ($xpath->query($xpathExpression) as $link) {
            $directDownloadXpathExpression = './docbook:ezattribute/docbook:ezvalue[@key="direct-download"]';
            $directDownload = $xpath->query($directDownloadXpathExpression, $link)->count() > 0
                && 'true' === $xpath->query($directDownloadXpathExpression, $link)->item(0)->nodeValue;

            $openInlineXpathExpression = './docbook:ezattribute/docbook:ezvalue[@key="open-inline"]';
            $openInline = $xpath->query($openInlineXpathExpression, $link)->count() > 0
                && 'true' === $xpath->query($openInlineXpathExpression, $link)->item(0)->nodeValue;

            if (!$directDownload) {
                continue;
            }

            $href = $link->getAttribute('xlink:href');
            preg_match('~^(.+://)?([^#]*)?(#.*|\\s*)?$~', $href, $matches);
            [, $scheme, $id, $fragment] = $matches;

            $content = null;
            if ('ezcontent://' === $scheme) {
                try {
                    $content = $this->loadService->loadContent((int) $id);
                } catch (APINotFoundException $e) {
                    if ($this->logger) {
                        $this->logger->warning(
                            'While generating links for richtext, could not locate ' .
                            'Content object with ID ' . $id,
                        );
                    }

                    continue;
                } catch (APIUnauthorizedException $e) {
                    if ($this->logger) {
                        $this->logger->notice(
                            'While generating links for richtext, unauthorized to load ' .
                            'Content object with ID ' . $id,
                        );
                    }

                    continue;
                }
            }

            if (null === $content && 'ezlocation://' === $scheme) {
                try {
                    $location = $this->loadService->loadLocation((int) $id);
                    $content = $location->content;
                } catch (APINotFoundException $e) {
                    if ($this->logger) {
                        $this->logger->warning(
                            'While generating links for richtext, could not locate ' .
                            'Location with ID ' . $id,
                        );
                    }

                    continue;
                } catch (APIUnauthorizedException $e) {
                    if ($this->logger) {
                        $this->logger->notice(
                            'While generating links for richtext, unauthorized to load ' .
                            'Location with ID ' . $id,
                        );
                    }

                    continue;
                }
            }

            if (null === $content) {
                continue;
            }

            if (!$content->hasField('file')) {
                continue;
            }

            $field = $content->getField('file');

            if ($field->isEmpty()) {
                continue;
            }

            $hrefResolved = $this->urlGenerator->generate('ngsite_download', [
                'contentId' => $content->id,
                'fieldId' => $field->id,
                'isInline' => $openInline,
            ]);

            $hrefAttributeName = 'xlink:href';

            // For embeds set the resolved href to the separate attribute
            // Original href needs to be preserved in order to generate link parameters
            // This will need to change with introduction of UrlService and removal of URL link
            // resolving in external storage
            if ('ezlink' === $link->localName) {
                $hrefAttributeName = 'href_resolved';
            }

            $link->setAttribute($hrefAttributeName, $hrefResolved);
        }

        return $xmlDoc;
    }
}
