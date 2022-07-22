<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use function count;

use const PHP_EOL;

class UpdatePublishDateCommand extends ContainerAwareCommand
{
    protected function configure(): void
    {
        $this->setName('ngsite:content:update-publish-date')
            ->setDescription('Updates publish date of all content of specified content type')
            ->addOption(
                'content-type',
                'c',
                InputOption::VALUE_REQUIRED,
                'Content type to update',
            )
            ->addOption(
                'field-def-identifier',
                'f',
                InputOption::VALUE_REQUIRED,
                'Field definition identifier containing publish date to read from',
            )
            ->addOption(
                'use-main-translation',
                null,
                InputOption::VALUE_NONE,
                'If specified, the script will use main translation instead of most prioritized one',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $questionHelper = $this->getHelper('question');

        $contentTypeIdentifier = $input->getOption('content-type');
        if (empty($contentTypeIdentifier)) {
            throw new RuntimeException("Parameter '--content-type' ('-c') is required");
        }

        $fieldDefIdentifier = $input->getOption('field-def-identifier');
        if (empty($fieldDefIdentifier)) {
            throw new RuntimeException("Parameter '--field-def-identifier' ('-f') is required");
        }

        $contentTypeService = $this->getContainer()->get('ezpublish.api.service.content_type');

        try {
            $contentType = $contentTypeService->loadContentTypeByIdentifier($contentTypeIdentifier);
        } catch (NotFoundException $e) {
            throw new RuntimeException("Content type '{$contentTypeIdentifier}' does not exist");
        }

        $fieldDefinition = $contentType->getFieldDefinition($fieldDefIdentifier);
        if (!$fieldDefinition instanceof FieldDefinition) {
            throw new RuntimeException("Field definition '{$fieldDefIdentifier}' does not exist in '{$contentTypeIdentifier}' content type");
        }

        if ($fieldDefinition->fieldTypeIdentifier !== 'ezdatetime' && $fieldDefinition->fieldTypeIdentifier !== 'ezdate') {
            throw new RuntimeException("Field definition '{$fieldDefIdentifier}' must be of 'ezdatetime' or 'ezdate' field type");
        }

        $searchService = $this->getContainer()->get('ezpublish.api.service.search');

        $query = new Query();
        $query->filter = new Criterion\ContentTypeIdentifier($contentTypeIdentifier);
        $query->limit = 0;

        $searchResult = $searchService->findContent($query, [], false);

        $totalCount = $searchResult->totalCount;
        if ($totalCount === 0) {
            $output->writeln("No content found for <comment>{$contentTypeIdentifier}</comment> content type.");

            return 1;
        }

        $question = new ConfirmationQuestion("Found <comment>{$totalCount}</comment> content items. Proceed? <info>[y/N]</info> ", false);
        if (!$questionHelper->ask($input, $output, $question)) {
            return 1;
        }

        $output->write(PHP_EOL);

        $fieldHelper = $this->getContainer()->get('ezpublish.field_helper');
        $repository = $this->getContainer()->get('ezpublish.api.repository');

        $progress = new ProgressBar($output, $searchResult->totalCount);
        $progress->start();
        $updatedCount = 0;

        $query = new Query();
        $query->filter = new Criterion\ContentTypeIdentifier($contentTypeIdentifier);
        $query->limit = 50;
        $query->offset = 0;

        $searchResult = $searchService->findContent($query, [], false);
        $searchHitCount = count($searchResult->searchHits);

        while ($searchHitCount > 0) {
            foreach ($searchResult->searchHits as $hit) {
                /** @var \eZ\Publish\API\Repository\Values\Content\Content $content */
                $content = $hit->valueObject;

                /** @var \eZ\Publish\Core\FieldType\DateAndTime\Value|\eZ\Publish\Core\FieldType\Date\Value $dateFieldValue */
                $dateFieldValue = $content->getField(
                    $fieldDefIdentifier,
                    $input->getOption('use-main-translation') ?
                        $content->contentInfo->mainLanguageCode :
                        null,
                )->value;

                $dateValueData = $fieldDefinition->fieldTypeIdentifier === 'ezdatetime' ? $dateFieldValue->value : $dateFieldValue->date;

                if (
                    !$fieldHelper->isFieldEmpty($content, $fieldDefIdentifier)
                    && $content->contentInfo->publishedDate->getTimestamp() !== $dateValueData->getTimestamp()
                ) {
                    $metadataUpdateStruct = $repository->getContentService()->newContentMetadataUpdateStruct();
                    $metadataUpdateStruct->publishedDate = $dateValueData;

                    $repository->sudo(
                        static fn (Repository $repository): Content => $repository->getContentService()->updateContentMetadata($content->contentInfo, $metadataUpdateStruct),
                    );

                    ++$updatedCount;
                }

                $progress->advance();
            }

            $query->offset += $query->limit;
            $searchResult = $searchService->findContent($query, [], false);
            $searchHitCount = count($searchResult->searchHits);
        }

        $progress->finish();

        $output->writeln(PHP_EOL . PHP_EOL . "Updated <comment>{$updatedCount}</comment> content items.");

        return 0;
    }
}
