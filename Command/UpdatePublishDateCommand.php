<?php

namespace Netgen\Bundle\MoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Helper\ProgressBar;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Repository;

class UpdatePublishDateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName( 'ngmore:content:update-publish-date' )
            ->setDescription( 'Updates publish date on provided content type.' )
            ->addOption(
                'content-type',
                'c',
                InputOption::VALUE_REQUIRED,
                'Content type to update'
            )
            ->addOption(
                'field-def-identifier',
                'f',
                InputOption::VALUE_REQUIRED,
                'Field definition identifier containing publish date override'
            )
            ->addOption(
                'use-main-translation',
                '-m',
                InputOption::VALUE_NONE,
                'Field definition identifier containing publish date override'
            )
        ;
    }

    protected function execute( InputInterface $input, OutputInterface $output )
    {
        $questionHelper = $this->getHelper( 'question' );

        if ( !$contentTypeIdentifier = $input->getOption( 'content-type' ) )
        {
            $output->writeln( "<error>Parameter 'content-type' ('-c') is required!</error>" );
            return 1;
        }

        if ( !$fieldDefIdentifier = $input->getOption( 'field-def-identifier' ) )
        {
            $output->writeln( "<error>Parameter 'field-definition-identifier' ('-f') is required!</error>" );
            return 1;
        }

        $contentTypeService = $this->getContainer()->get( 'ezpublish.api.service.content_type' );
        $contentType = $contentTypeService->loadContentTypeByIdentifier( $contentTypeIdentifier );

        if ( !$fieldDefinition = $contentType->getFieldDefinition( $fieldDefIdentifier ) )
        {
            $output->writeln( "<error>Field '{$fieldDefIdentifier}' does not exist in '{$contentTypeIdentifier}' content type'</error>" );
            return 1;
        }

        if ( $fieldDefinition->fieldTypeIdentifier !== 'ezdatetime' && $fieldDefinition->fieldTypeIdentifier !== 'ezdate' )
        {
            $output->writeln( "<error>Field '{$fieldDefIdentifier}' must be of 'ezdatetime' or 'ezdate' field type</error>" );
            return 1;
        }

        /** @var \eZ\Publish\API\Repository\SearchService $searchService */
        $searchService = $this->getContainer()->get( 'ezpublish.api.service.search' );

        $query = new Query();
        $query->filter = new Criterion\ContentTypeIdentifier( $contentTypeIdentifier );
        $query->limit = 0;

        $searchResult = $searchService->findContent( $query, array(), false );

        $totalCount = $searchResult->totalCount;

        if ( !$totalCount > 0 )
        {
            $output->writeln( 'No content found with given parameters!' );
            $output->writeln( 'Canceling...' );
            return 1;
        }

        $output->writeln( "Found {$totalCount} content." );
        $question = new ConfirmationQuestion( 'Proceed?[y/n] ', false );

        if ( !$questionHelper->ask( $input, $output, $question ) )
        {
            $output->writeln( 'Canceling...' );
            return 1;
        }

        /** @var \eZ\Publish\Core\Helper\TranslationHelper $translationHelper */
        $translationHelper = $this->getContainer()->get( 'ezpublish.translation_helper' );
        /** @var \eZ\Publish\Core\Helper\FieldHelper $fieldHelper */
        $fieldHelper = $this->getContainer()->get( 'ezpublish.field_helper' );
        /** @var \eZ\Publish\API\Repository\Repository $repository */
        $repository = $this->getContainer()->get( 'ezpublish.api.repository' );

        $progress=new ProgressBar( $output, $searchResult->totalCount );
        $progress->start();
        $updated = 0;

        $query = new Query();
        $query->filter = new Criterion\ContentTypeIdentifier( $contentTypeIdentifier );
        $query->limit = 50;
        $query->offset = 0;

        $searchResult = $searchService->findContent( $query, array(), false );

        while ( count( $searchResult->searchHits ) > 0 )
        {
            foreach( $searchResult->searchHits as $hit )
            {
                /** @var \eZ\Publish\API\Repository\Values\Content\Content $content */
                $content = $hit->valueObject;

                if ( $input->getOption( 'use-main-translation' ) )
                {
                    /** @var \eZ\Publish\Core\FieldType\DateAndTime\Value $dateField */
                    $dateFieldValue = $content->getFieldValue( $fieldDefIdentifier );
                }
                else
                {
                    $dateFieldValue = $translationHelper->getTranslatedField( $content, $fieldDefIdentifier )->value;
                }

                $dateValueData = $fieldDefinition->fieldTypeIdentifier === 'ezdatetime' ? $dateFieldValue->value : $dateFieldValue->date;

                if ( !$fieldHelper->isFieldEmpty( $content, $fieldDefIdentifier ) &&
                    $content->contentInfo->publishedDate->getTimestamp() !== $dateValueData->getTimestamp() )
                {

                    $metadataUpdateStruct = $repository->getContentService()->newContentMetadataUpdateStruct();
                    $metadataUpdateStruct->publishedDate = $dateValueData;

                    $repository->sudo(
                        function ( Repository $repository ) use ( $content, $metadataUpdateStruct )
                        {
                            return $repository->getContentService()->updateContentMetadata( $content->contentInfo, $metadataUpdateStruct );
                        }
                    );

                    $updated++;
                }

                $progress->advance();
            }

            $query->offset = $query->offset + 50;
            $searchResult = $searchService->findContent( $query, array(), false );
        }

        $progress->finish();

        $output->writeln( '' );
        $output->writeln( "<info>Updated the total of {$updated} content</info>" );

        return 0;
    }
}
