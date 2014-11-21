<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use eZ\Publish\Core\FieldType\BinaryBase\Value as BinaryBaseValue;
use eZ\Publish\Core\FieldType\Image\Value as ImageValue;
use eZ\Publish\API\Repository\Values\Content\Field;
use eZ\Bundle\EzPublishIOBundle\BinaryStreamResponse;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
            throw new NotFoundHttpException(
                $this->container->get( 'translator' )->trans(
                    'ngmore.download.file_not_found'
                )
            );
        }
        catch ( UnauthorizedException $e )
        {
            throw new AccessDeniedHttpException(
                $this->container->get( 'translator' )->trans(
                    'ngmore.download.access_denied'
                )
            );
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
            throw new NotFoundHttpException(
                $this->container->get( 'translator' )->trans(
                    'ngmore.download.file_not_found'
                )
            );
        }

        if ( $binaryFileField->value instanceof BinaryBaseValue )
        {
            $ioService = $this->container->get( 'ezpublish.fieldtype.ezbinaryfile.io_service' );
            $binaryFile = $ioService->loadBinaryFileByUri( $binaryFileField->value->uri );
        }
        else if ( $binaryFileField->value instanceof ImageValue )
        {
            $ioService = $this->container->get( 'ezpublish.fieldType.ezimage.io_service' );
            $binaryFile = $ioService->loadBinaryFile( $binaryFileField->value->id );
        }
        else
        {
            throw new NotFoundHttpException(
                $this->container->get( 'translator' )->trans(
                    'ngmore.download.file_not_found'
                )
            );
        }

        $fallbackFileName = $binaryFile->id;
        if ( strpos( $fallbackFileName, '/' ) > 0 )
        {
            $fallbackFileName = substr( $fallbackFileName, strrpos( $fallbackFileName, '/' ) + 1 );
        }

        $response = new BinaryStreamResponse( $binaryFile, $ioService );
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $binaryFileField->value->fileName,
            $fallbackFileName
        );

        return $response;
    }
}
