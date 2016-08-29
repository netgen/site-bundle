<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use Netgen\Bundle\EzPlatformSiteApiBundle\Controller\Controller;
use eZ\Publish\Core\FieldType\BinaryBase\Value as BinaryBaseValue;
use eZ\Publish\Core\FieldType\Image\Value as ImageValue;
use eZ\Bundle\EzPublishIOBundle\BinaryStreamResponse;
use eZ\Publish\Core\IO\IOServiceInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DownloadController extends Controller
{
    /**
     * @var \Netgen\EzPlatformSiteApi\API\LoadService
     */
    protected $loadService;

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
     * Constructor.
     *
     * @param \eZ\Publish\Core\IO\IOServiceInterface $ioFileService
     * @param \eZ\Publish\Core\IO\IOServiceInterface $ioImageService
     * @param \Symfony\Component\Translation\TranslatorInterface $translator
     */
    public function __construct(
        IOServiceInterface $ioFileService,
        IOServiceInterface $ioImageService,
        TranslatorInterface $translator
    )
    {
        $this->ioFileService = $ioFileService;
        $this->ioImageService = $ioImageService;
        $this->translator = $translator;

        $this->loadService = $this->getSite()->getLoadService();
    }

    /**
     * Downloads the binary file specified by content ID and field ID.
     *
     * Assumes that the file is locally stored
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
    public function downloadFile(Request $request, $contentId, $fieldId, $isInline = false)
    {
        $content = $this->loadService->loadContent(
            $contentId,
            $request->query->has('version') ? $request->query->get('version') : null,
            $request->query->has('inLanguage') ? $request->query->get('inLanguage') : null
        );

        if (!$content->hasFieldById($fieldId) || $content->getFieldById($fieldId)->isEmpty()) {
            throw new NotFoundHttpException(
                $this->translator->trans('ngmore.download.file_not_found')
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
                $this->translator->trans('ngmore.download.file_not_found')
            );
        }

        $response = new BinaryStreamResponse($binaryFile, $ioService);
        $response->setContentDisposition(
            (bool)$isInline ? ResponseHeaderBag::DISPOSITION_INLINE :
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            str_replace(array('/', '\\'), '', $binaryFieldValue->fileName),
            'file'
        );

        return $response;
    }
}
