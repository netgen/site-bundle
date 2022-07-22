<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller;

use eZ\Bundle\EzPublishIOBundle\BinaryStreamResponse;
use eZ\Publish\Core\FieldType\BinaryBase\Value as BinaryBaseValue;
use eZ\Publish\Core\FieldType\Image\Value as ImageValue;
use eZ\Publish\Core\IO\IOServiceInterface;
use Netgen\Bundle\SiteBundle\Event\Content\DownloadEvent;
use Netgen\Bundle\SiteBundle\Event\SiteEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\TranslatorInterface;

use function str_replace;

class DownloadController extends Controller
{
    protected IOServiceInterface $ioFileService;

    protected IOServiceInterface $ioImageService;

    protected TranslatorInterface $translator;

    protected EventDispatcherInterface $dispatcher;

    public function __construct(
        IOServiceInterface $ioFileService,
        IOServiceInterface $ioImageService,
        TranslatorInterface $translator,
        EventDispatcherInterface $dispatcher
    ) {
        $this->ioFileService = $ioFileService;
        $this->ioImageService = $ioImageService;
        $this->translator = $translator;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Downloads the binary file specified by content ID and field ID.
     *
     * Assumes that the file is locally stored
     *
     * Dispatch \Netgen\Bundle\SiteBundle\Event\SiteEvents::CONTENT_DOWNLOAD only once
     *
     * @param mixed $contentId
     * @param mixed $fieldId
     * @param bool $isInline
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If file or image does not exist
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException If content has all of its locations hidden
     */
    public function downloadFile(Request $request, $contentId, $fieldId, $isInline = false): BinaryStreamResponse
    {
        $content = $this->getSite()->getLoadService()->loadContent(
            $contentId,
            $request->query->get('version'),
            $request->query->get('inLanguage'),
        );

        if (!$content->hasFieldById($fieldId) || $content->getFieldById($fieldId)->isEmpty()) {
            throw $this->createNotFoundException(
                $this->translator->trans('download.file_not_found', [], 'ngsite'),
            );
        }

        $canAccess = false;
        foreach ($content->getLocations() as $location) {
            if (!$location->invisible) {
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
            str_replace(['/', '\\'], '', $binaryFieldValue->fileName),
            'file',
        );

        if (!$request->headers->has('Range')) {
            $downloadEvent = new DownloadEvent(
                $contentId,
                $fieldId,
                $content->contentInfo->currentVersionNo,
                $response,
            );

            $this->dispatcher->dispatch(SiteEvents::CONTENT_DOWNLOAD, $downloadEvent);
        }

        return $response;
    }
}
