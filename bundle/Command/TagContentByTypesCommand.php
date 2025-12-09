<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
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
use function mb_trim;
use function sprintf;

final class TagContentByTypesCommand extends Command
{
    private SymfonyStyle $style;

    public function __construct(
        private Repository $repository,
        private ContentService $contentService,
        private SearchService $searchService,
        private TagsService $tagsService,
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
            ->addOption('field-identifier', null, InputOption::VALUE_REQUIRED);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->style = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $query = new Query();
        $query->filter = new Criterion\LogicalAnd([
            new Criterion\ParentLocationId($this->getParentLocationId((int) $input->getOption('parent-location'))),
            new Criterion\ContentTypeIdentifier($this->getContentTypes($input->getOption('content-types'))),
        ]);

        $batchSize = 50;

        $searchResults = $this->searchService->findContent($query);
        $totalResults = $searchResults->totalCount ?? 0;

        $this->style->newLine();
        $this->style->progressStart($totalResults);

        $fieldIdentifier = $input->getOption('field-identifier');

        for ($offset = 0; $offset < $totalResults; $offset += $batchSize) {
            $query->offset = $offset;
            $query->limit = $batchSize;
            $searchResults = $this->searchService->findContent($query);

            foreach ($searchResults->searchHits as $searchHit) {
                /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $content */
                $content = $searchHit->valueObject;

                if ($content->getField($fieldIdentifier) === null) {
                    continue;
                }

                if (!$content->getField($fieldIdentifier)->value instanceof TagFieldValue) {
                    $this->style->warning(sprintf('Field "%s" must be of "eztags" type in content with ID #%d', $fieldIdentifier, $content->id));

                    continue;
                }

                $tag = $this->tagsService->loadTag($this->getTagId((int) $input->getOption('tag-id')));
                $valueTags = $content->getFieldValue($fieldIdentifier)->tags;

                foreach ($valueTags as $valueTag) {
                    // Skip content which already has the provided tag
                    if ($valueTag->id === $tag->id) {
                        continue 2;
                    }
                }

                $valueTags[] = $tag;

                $this->repository->sudo(
                    function (Repository $repository) use ($content, $fieldIdentifier, $valueTags): void {
                        $repository->beginTransaction();

                        try {
                            $contentDraft = $this->contentService->createContentDraft($content->contentInfo);
                            $contentUpdateStruct = $this->contentService->newContentUpdateStruct();

                            $contentUpdateStruct->setField($fieldIdentifier, new TagFieldValue($valueTags));

                            $this->contentService->updateContent($contentDraft->versionInfo, $contentUpdateStruct);
                            $this->contentService->publishVersion($contentDraft->versionInfo);

                            $repository->commit();
                        } catch (Throwable $t) {
                            $this->style->error($t->getMessage());

                            $repository->rollback();
                        }
                    },
                );

                $this->style->progressAdvance();
            }
        }

        $this->style->progressFinish();
        $this->style->success('Tags assigned successfully');

        return Command::SUCCESS;
    }

    private function getParentLocationId(int $parentLocationId): int
    {
        if ($parentLocationId < 1) {
            throw new InvalidOptionException(
                sprintf('Argument --parent-location must be an integer > 0, you provided "%d"', $parentLocationId),
            );
        }

        return $parentLocationId;
    }

    /**
     * @return array<string>
     */
    private function getContentTypes(string $contentTypes): array
    {
        return $this->parseCommaDelimited($contentTypes);
    }

    private function getTagId(int $tagId): int
    {
        if ($tagId < 1) {
            throw new InvalidOptionException(
                sprintf('Argument --tag-id must be an integer > 0, you provided "%d"', $tagId),
            );
        }

        return $tagId;
    }

    /**
     * @return array<string>
     */
    private function parseCommaDelimited(string $value): array
    {
        $value = mb_trim($value);

        if ($value === '') {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('trim', explode(',', $value)))));
    }
}
