<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Repository\SearchService;
use Netgen\TagsBundle\API\Repository\TagsService;
use Netgen\TagsBundle\Core\FieldType\Tags\Value as TagFieldValue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function explode;
use function sprintf;
use function trim;

final class TagContentByTypesCommand extends Command
{
    private SymfonyStyle $style;

    public function __construct(
        private Repository $repository,
        private ContentService $contentService,
        private SearchService $searchService,
        private TagsService $tagsService,
        private ConfigResolverInterface $configResolver,
    ) {
        // Parent constructor call is mandatory for commands registered as services
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Add tags to content')
            ->addOption('parent-location', null, InputOption::VALUE_REQUIRED)
            ->addOption('content-types', null, InputOption::VALUE_REQUIRED)
            ->addOption('tag-id', null, InputOption::VALUE_REQUIRED)
            ->addOption('field-identifier', null, InputOption::VALUE_OPTIONAL);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->style = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $query = new Query();
        $query->filter = new Criterion\LogicalAnd([
            new Criterion\ParentLocationId($this->getParentLocation((int) $input->getOption('parent-location'))),
            new Criterion\ContentTypeIdentifier($this->getContentTypes($input->getOption('content-types'))),
        ]);

        $batchSize = 50;

        $searchResults = $this->searchService->findContent($query);
        $totalResults = $searchResults->totalCount;

        $this->style->newLine();
        $this->style->progressStart($totalResults);

        $fieldIdentifierInput = $input->getOption('field-identifier');
        if ($fieldIdentifierInput === null) {
            $fieldIdentifier = $this->configResolver->getParameter('tag_command_default_field_identifier', 'ngsite')[0];
        } else {
            $fieldIdentifier = $fieldIdentifierInput;
        }

        for ($offset = 0; $offset < $totalResults; $offset += $batchSize) {
            $query->offset = $offset;
            $query->limit = $batchSize;
            $searchResults = $this->searchService->findContent($query);

            $this->repository->beginTransaction();

            foreach ($searchResults->searchHits as $searchHit) {
                /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $content */
                $content = $searchHit->valueObject;

                if ($this->hasField($content, $fieldIdentifier) === false) {
                    continue;
                }

                try {
                    if (!$content->getField($fieldIdentifier)->value instanceof TagFieldValue) {
                        $this->style->error(sprintf('Field with identifier %s must be a type of eztags', $fieldIdentifier));

                        $this->repository->rollback();

                        return Command::FAILURE;
                    }

                    $alreadyAssignedTags = $content->getFieldValue($fieldIdentifier)->tags;
                    $tag = $this->tagsService->loadTag($this->getTagId((int) $input->getOption('tag-id')));
                    $tagsToAssign = array_filter($alreadyAssignedTags, fn ($alreadyAssignedTag) => $tag->id !== $alreadyAssignedTag->id);
                    $tagsToAssign[] = $tag;

                    $this->repository->sudo(
                        function () use ($content, $fieldIdentifier, $tagsToAssign): void {
                            $contentDraft = $this->contentService->createContentDraft($content->contentInfo);
                            $contentUpdateStruct = $this->contentService->newContentUpdateStruct();

                            $contentUpdateStruct->setField($fieldIdentifier, new TagFieldValue($tagsToAssign));

                            $this->contentService->updateContent($contentDraft->versionInfo, $contentUpdateStruct);
                            $this->contentService->publishVersion($contentDraft->versionInfo);
                        }
                    );

                    $this->style->progressAdvance();
                } catch (Throwable $t) {
                    $this->style->error($t->getMessage());

                    $this->repository->rollback();

                    continue;
                }
            }
            $this->repository->commit();
        }

        $this->style->progressFinish();
        $this->style->success('Tags assigned successfully');

        return Command::SUCCESS;
    }

    protected function getParentLocation(int $parentLocationInput): int
    {
        if (!is_numeric($parentLocationInput) || (int)$parentLocationInput < 1) {
            throw new InvalidOptionException(
                sprintf("Argument --parent-location must be an integer > 0, you provided '%s'", $parentLocationInput),
            );
        }

        return (int) $parentLocationInput;
    }

    protected function getContentTypes(string $contentTypesInput): array
    {
        return $this->parseCommaDelimited($contentTypesInput);
    }

    protected function getTagId(int $tagIdInput): int
    {
        if (!is_numeric($tagIdInput) || (int)$tagIdInput < 1) {
            throw new InvalidOptionException(
                sprintf("Argument --tag-id must be an integer > 0, you provided '%s'", $tagIdInput),
            );
        }

        return (int) $tagIdInput;
    }

    private function parseCommaDelimited(?string $value): array
    {
        $value = trim($value ?? '');

        if ($value === '') {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('trim', explode(',', $value)))));
    }

    private function hasField(Content $content, string $fieldIdentifier): bool
    {
        $contentType = $this->repository->getContentTypeService()->loadContentType($content->contentInfo->contentTypeId);

        return $contentType->hasFieldDefinition($fieldIdentifier);
    }
}
