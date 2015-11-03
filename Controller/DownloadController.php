<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\Core\FieldType\BinaryBase\Value as BinaryBaseValue;
use eZ\Publish\Core\FieldType\Image\Value as ImageValue;
use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Bundle\EzPublishIOBundle\BinaryStreamResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DownloadController extends Controller
{
    /**
     * Downloads the binary file specified by content ID and field ID
     *
     * Assumes that the file is locally stored
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param mixed $contentId
     * @param mixed $fieldId
     * @param bool $isInline
     *
     * @return \eZ\Bundle\EzPublishIOBundle\BinaryStreamResponse
     */
    public function downloadFile( Request $request, $contentId, $fieldId, $isInline = false )
    {
        $content = $this->getRepository()->getContentService()->loadContent(
            $contentId,
            null,
            $request->query->has( 'version' ) ? $request->query->get( 'version' ) : null
        );

        $binaryField = null;
        foreach ( $content->getFields() as $field )
        {
            if ( $field->id == $fieldId )
            {
                $binaryField = $field;
                break;
            }
        }

        if (
            !$binaryField instanceof Field ||
            $this->container->get( 'ezpublish.field_helper' )->isFieldEmpty(
                $content,
                $binaryField->fieldDefIdentifier,
                $request->query->has( 'inLanguage' ) ? $request->query->get( 'inLanguage' ) : null
            )
        )
        {
            throw new NotFoundHttpException(
                $this->container->get( 'translator' )->trans(
                    'ngmore.download.file_not_found'
                )
            );
        }

        $binaryFieldValue = $this->container->get( 'ezpublish.translation_helper' )->getTranslatedField(
            $content,
            $binaryField->fieldDefIdentifier,
            $request->query->has( 'inLanguage' ) ? $request->query->get( 'inLanguage' ) : null
        )->value;

        if ( $binaryFieldValue instanceof BinaryBaseValue )
        {
            $ioService = $this->container->get( 'ezpublish.fieldtype.ezbinaryfile.io_service' );
            $binaryFile = $ioService->loadBinaryFile( $binaryFieldValue->id );
        }
        else if ( $binaryFieldValue instanceof ImageValue )
        {
            $ioService = $this->container->get( 'ezpublish.fieldtype.ezimage.io_service' );
            $binaryFile = $ioService->loadBinaryFile( $binaryFieldValue->id );
        }
        else
        {
            throw new NotFoundHttpException(
                $this->container->get( 'translator' )->trans(
                    'ngmore.download.file_not_found'
                )
            );
        }

        $response = new BinaryStreamResponse( $binaryFile, $ioService );
        $response->setContentDisposition(
            (bool)$isInline ? ResponseHeaderBag::DISPOSITION_INLINE :
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            str_replace( array( '/', '\\' ), '', $binaryFieldValue->fileName ),
            'file'
        );

        return $response;
    }
}
