<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller\EmbedView;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Netgen\Bundle\IbexaSiteApiBundle\View\ContentView;
use Netgen\Bundle\SiteBundle\Controller\Controller;
use Netgen\IbexaSiteApi\API\Site;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

use function in_array;
use function mb_substr;
use function sprintf;
use function str_starts_with;
use function trim;

final class Image extends Controller
{
    public function __construct(private Site $site, private LoggerInterface $logger = new NullLogger())
    {
    }

    /**
     * Action for viewing embedded content with image content type identifier.
     */
    public function __invoke(ContentView $view): ContentView
    {
        $parameters = $view->getParameters();
        $targetLink = trim($parameters['objectParameters']['href'] ?? '');

        if ($targetLink !== '') {
            $location = null;
            $content = null;

            if (str_starts_with($targetLink, 'ezlocation://')) {
                $locationId = (int) mb_substr($targetLink, 9);

                try {
                    $location = $this->site->getLoadService()->loadLocation($locationId);
                    $content = $location->content;
                } catch (NotFoundException) {
                    $targetLink = null;

                    $this->logger->error(sprintf('Tried to generate link to non existing location #%s', $locationId));
                } catch (UnauthorizedException) {
                    $targetLink = null;

                    $this->logger->error(sprintf('Tried to generate link to location #%s without read rights', $locationId));
                }
            } elseif (str_starts_with($targetLink, 'ezcontent://')) {
                $linkedContentId = (int) mb_substr($targetLink, 11);

                try {
                    $content = $this->site->getLoadService()->loadContent($linkedContentId);
                } catch (NotFoundException) {
                    $targetLink = null;

                    $this->logger->error(sprintf('Tried to generate link to non existing content #%s', $linkedContentId));
                } catch (UnauthorizedException) {
                    $targetLink = null;

                    $this->logger->error(sprintf('Tried to generate link to content #%s without read rights', $linkedContentId));
                }
            }

            $directDownloadLink = null;
            $isDirectDownload = in_array($parameters['objectParameters']['link_direct_download'], [true, '1'], true);

            if ($content !== null && $isDirectDownload) {
                $fieldName = null;
                if ($content->hasField('file') && !$content->getField('file')->isEmpty()) {
                    $fieldName = 'file';
                } elseif ($content->hasField('image') && !$content->getField('image')->isEmpty()) {
                    $fieldName = 'image';
                }

                if ($fieldName !== null) {
                    $directDownloadLink = $this->generateUrl(
                        'ngsite_download',
                        [
                            'contentId' => $content->id,
                            'fieldId' => $content->getField($fieldName)->id,
                        ],
                    );
                }
            }

            if ($directDownloadLink !== null) {
                $targetLink = $directDownloadLink;
            } elseif (str_starts_with($targetLink, 'ezlocation://')) {
                $targetLink = $this->generateUrl('', [RouteObjectInterface::ROUTE_OBJECT => $location]);
            } elseif (str_starts_with($targetLink, 'ezcontent://')) {
                $targetLink = $this->generateUrl('', [RouteObjectInterface::ROUTE_OBJECT => $content]);
            }
        }

        $view->addParameters([
            'link_href' => $targetLink,
        ]);

        return $view;
    }
}
