<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\Core\FieldType\BinaryBase\Value as BinaryBaseValue;
use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Bundle\EzPublishIOBundle\BinaryStreamResponse;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DownloadController extends Controller
{
    /**
     * Downloads the binary file specified by content ID and field ID
     *
     * Assumes that the file is locally stored
     *
     * @param mixed $contentId
     * @param mixed $fieldId
     *
     * @return \eZ\Bundle\EzPublishIOBundle\BinaryStreamResponse
     */
    public function downloadFile( $contentId, $fieldId )
    {
        try
        {
            $content = $this->getRepository()->getContentService()->loadContent( $contentId );
        }
        catch ( NotFoundException $e )
        {
            throw new NotFoundHttpException( 'File not found' );
        }

        $binaryFileField = null;
        foreach ( $content->getFields() as $field )
        {
            if ( $field->id == $fieldId )
            {
                $binaryFileField = $field;
                break;
            }
        }

        if (
            !$binaryFileField instanceof Field ||
            $this->container->get( 'ezpublish.field_helper' )->isFieldEmpty(
                $content,
                $binaryFileField->fieldDefIdentifier
            )
        )
        {
            throw new NotFoundHttpException( 'File not found' );
        }

        if ( !$binaryFileField->value instanceof BinaryBaseValue )
        {
            throw new NotFoundHttpException( 'File not found' );
        }

        $ioService = $this->container->get( 'ezpublish.fieldtype.ezbinaryfile.io_service' );

        $response = new BinaryStreamResponse(
            $ioService->loadBinaryFileByUri( $binaryFileField->value->uri ),
            $ioService
        );

        $fallbackFileName = $binaryFileField->value->id;
        if ( strpos( $fallbackFileName, '/' ) > 0 )
        {
            $fallbackFileName = substr( $fallbackFileName, strrpos( $fallbackFileName, '/' ) + 1 );
        }

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $binaryFileField->value->fileName,
            $fallbackFileName
        );

        return $response;
    }
}
