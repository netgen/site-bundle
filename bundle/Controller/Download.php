<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller;

use Ibexa\Bundle\IO\BinaryStreamResponse;
use Ibexa\Core\FieldType\BinaryBase\Value as BinaryBaseValue;
use Ibexa\Core\FieldType\Image\Value as ImageValue;
use Ibexa\Core\IO\IOServiceInterface;
use Netgen\Bundle\SiteBundle\Event\Content\DownloadEvent;
use Netgen\Bundle\SiteBundle\Event\SiteEvents;
use Netgen\IbexaSiteApi\API\Site;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function str_replace;

final class Download extends Controller
{
    public function __construct(
        private Site $site,
        private IOServiceInterface $ioFileService,
        private IOServiceInterface $ioImageService,
        private TranslatorInterface $translator,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * Downloads the binary file specified by content ID and field ID.
     *
     * Assumes that the file is locally stored
     *
     * Dispatch \Netgen\Bundle\SiteBundle\Event\SiteEvents::CONTENT_DOWNLOAD only once
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If file or image does not exist
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException If content has all of its locations hidden
     */
    public function __invoke(Request $request, int|string $contentId, int|string $fieldId, bool|string $isInline = false): BinaryStreamResponse
    {
        $contentId = (int) $contentId;
        $fieldId = (int) $fieldId;

        $content = $this->site->getLoadService()->loadContent($contentId);

        if (!$content->hasFieldById($fieldId) || $content->getFieldById($fieldId)->isEmpty()) {
            throw $this->createNotFoundException(
                $this->translator->trans('download.file_not_found', [], 'ngsite'),
            );
        }

        $canAccess = false;
        foreach ($content->getLocations() as $location) {
            if ($location->isVisible) {
                $canAccess = true;

                break;
            }
        }

        if (!$canAccess) {
            throw $this->createAccessDeniedException();
        }

        $binaryFieldValue = $content->getFieldById($fieldId)->value;

        if ($binaryFieldValue instanceof BinaryBaseValue) {
            $ioService = $this->ioFileService;
            $binaryFile = $this->ioFileService->loadBinaryFile($binaryFieldValue->id);
        } elseif ($binaryFieldValue instanceof ImageValue) {
            $ioService = $this->ioImageService;
            $binaryFile = $this->ioImageService->loadBinaryFile($binaryFieldValue->id);
        } else {
            throw $this->createNotFoundException(
                $this->translator->trans('download.file_not_found', [], 'ngsite'),
            );
        }

        $response = new BinaryStreamResponse($binaryFile, $ioService);
        $response->setContentDisposition(
            (bool) $isInline ? ResponseHeaderBag::DISPOSITION_INLINE :
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            str_replace(['/', '\\'], '', $binaryFieldValue->fileName ?? ''),
            'file',
        );

        if (!$request->headers->has('Range')) {
            $downloadEvent = new DownloadEvent(
                $contentId,
                $fieldId,
                $content->contentInfo->currentVersionNo,
                $response,
            );

            $this->dispatcher->dispatch($downloadEvent, SiteEvents::CONTENT_DOWNLOAD);
        }

        return $response;
    }
}
