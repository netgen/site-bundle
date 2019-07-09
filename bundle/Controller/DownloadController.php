<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Controller;

use eZ\Bundle\EzPublishIOBundle\BinaryStreamResponse;
use eZ\Publish\Core\FieldType\BinaryBase\Value as BinaryBaseValue;
use eZ\Publish\Core\FieldType\Image\Value as ImageValue;
use eZ\Publish\Core\IO\IOServiceInterface;
use Netgen\Bundle\SiteBundle\Event\Content\DownloadEvent;
use Netgen\Bundle\SiteBundle\Event\SiteEvents;
use Netgen\EzPlatformSiteApi\API\Site;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class DownloadController extends Controller
{
    /**
     * @var \Netgen\EzPlatformSiteApi\API\Site
     */
    protected $site;

    /**
     * @var \eZ\Publish\Core\IO\IOServiceInterface
     */
    protected $ioFileService;

    /**
     * @var \eZ\Publish\Core\IO\IOServiceInterface
     */
    protected $ioImageService;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $dispatcher;

    public function __construct(
        Site $site,
        IOServiceInterface $ioFileService,
        IOServiceInterface $ioImageService,
        TranslatorInterface $translator,
        EventDispatcherInterface $dispatcher
    ) {
        $this->site = $site;
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
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param mixed $contentId
     * @param mixed $fieldId
     * @param bool $isInline
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException If file or image does not exist
     *
     * @return \eZ\Bundle\EzPublishIOBundle\BinaryStreamResponse
     */
    public function downloadFile(Request $request, $contentId, $fieldId, $isInline = false): BinaryStreamResponse
    {
        $content = $this->site->getLoadService()->loadContent(
            $contentId,
            $request->query->get('version'),
            $request->query->get('inLanguage')
        );

        if (!$content->hasFieldById($fieldId) || $content->getFieldById($fieldId)->isEmpty()) {
            throw new NotFoundHttpException(
                $this->translator->trans('download.file_not_found', [], 'ngsite')
            );
        }

        $binaryFieldValue = $content->getFieldById($fieldId)->value;

        if ($binaryFieldValue instanceof BinaryBaseValue) {
            $ioService = $this->ioFileService;
            $binaryFile = $this->ioFileService->loadBinaryFile($binaryFieldValue->id);
        } elseif ($binaryFieldValue instanceof ImageValue) {
            $ioService = $this->ioImageService;
            $binaryFile = $this->ioImageService->loadBinaryFile($binaryFieldValue->id);
        } else {
            throw new NotFoundHttpException(
                $this->translator->trans('download.file_not_found', [], 'ngsite')
            );
        }

        $response = new BinaryStreamResponse($binaryFile, $ioService);
        $response->setContentDisposition(
            (bool) $isInline ? ResponseHeaderBag::DISPOSITION_INLINE :
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            str_replace(['/', '\\'], '', $binaryFieldValue->fileName),
            'file'
        );

        if (!$request->headers->has('Range')) {
            $downloadEvent = new DownloadEvent(
                $contentId,
                $fieldId,
                $content->contentInfo->currentVersionNo,
                $response
            );

            $this->dispatcher->dispatch($downloadEvent, SiteEvents::CONTENT_DOWNLOAD);
        }

        return $response;
    }
}
