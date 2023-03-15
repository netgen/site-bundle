<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\FieldType\Date\Value as DateValue;
use Ibexa\Core\FieldType\DateAndTime\Value as DateAndTimeValue;
use Ibexa\Core\Helper\FieldHelper;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use function count;
use function sprintf;

use const PHP_EOL;

final class UpdatePublishDateCommand extends Command
{
    public function __construct(private Repository $repository, private FieldHelper $fieldHelper)
    {
        // Parent constructor call is mandatory for commands registered as services
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Updates publish date of all content of specified content type')
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var \Symfony\Component\Console\Helper\QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');

        $contentTypeIdentifier = $input->getOption('content-type') ?? '';
        if ($contentTypeIdentifier === '') {
            throw new RuntimeException("Parameter '--content-type' ('-c') is required");
        }

        $fieldDefIdentifier = $input->getOption('field-def-identifier') ?? '';
        if ($fieldDefIdentifier === '') {
            throw new RuntimeException("Parameter '--field-def-identifier' ('-f') is required");
        }

        try {
            $contentType = $this->repository->getContentTypeService()->loadContentTypeByIdentifier($contentTypeIdentifier);
        } catch (NotFoundException) {
            throw new RuntimeException("Content type '" . $contentTypeIdentifier . "' does not exist");
        }

        $fieldDefinition = $contentType->getFieldDefinition($fieldDefIdentifier);
        if (!$fieldDefinition instanceof FieldDefinition) {
            throw new RuntimeException("Field definition '" . $fieldDefIdentifier . "' does not exist in '" . $contentTypeIdentifier . "' content type");
        }

        if ($fieldDefinition->fieldTypeIdentifier !== 'ezdatetime' && $fieldDefinition->fieldTypeIdentifier !== 'ezdate') {
            throw new RuntimeException("Field definition '" . $fieldDefIdentifier . "' must be of 'ezdatetime' or 'ezdate' field type");
        }

        $searchService = $this->repository->getSearchService();

        $query = new Query();
        $query->filter = new Criterion\ContentTypeIdentifier($contentTypeIdentifier);
        $query->limit = 0;

        $searchResult = $searchService->findContent($query, [], false);

        $totalCount = $searchResult->totalCount;
        if ($totalCount === 0) {
            $output->writeln('No content found for <comment>' . $contentTypeIdentifier . '</comment> content type.');

            return Command::FAILURE;
        }

        $question = new ConfirmationQuestion('Found <comment>' . $totalCount . '</comment> content items. Proceed? <info>[y/N]</info> ', false);
        if (!((bool) $questionHelper->ask($input, $output, $question))) {
            return Command::FAILURE;
        }

        $output->write(PHP_EOL);

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
                /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $content */
                $content = $hit->valueObject;

                $dateFieldValue = $content->getField(
                    $fieldDefIdentifier,
                    (bool) $input->getOption('use-main-translation') ?
                        $content->contentInfo->mainLanguageCode :
                        null,
                )->value;

                $dateValueData = match (true) {
                    $dateFieldValue instanceof DateAndTimeValue => $dateFieldValue->value,
                    $dateFieldValue instanceof DateValue => $dateFieldValue->date,
                    default => throw new RuntimeException(
                        sprintf(
                            'Field "%s" is of wrong type, ezdatetime or ezdate expected.',
                            $fieldDefIdentifier,
                        ),
                    ),
                };

                if (
                    !$this->fieldHelper->isFieldEmpty($content, $fieldDefIdentifier)
                    && $content->contentInfo->publishedDate->getTimestamp() !== $dateValueData->getTimestamp()
                ) {
                    $metadataUpdateStruct = $this->repository->getContentService()->newContentMetadataUpdateStruct();
                    $metadataUpdateStruct->publishedDate = $dateValueData;

                    $this->repository->sudo(
                        fn (): Content => $this->repository->getContentService()->updateContentMetadata($content->contentInfo, $metadataUpdateStruct),
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

        $output->writeln(PHP_EOL . PHP_EOL . 'Updated <comment>' . $updatedCount . '</comment> content items.');

        return Command::SUCCESS;
    }
}
