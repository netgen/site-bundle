<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command;

use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Netgen\TagsBundle\API\Repository\TagsService;
use Netgen\TagsBundle\API\Repository\Values\Tags\Tag;
use Netgen\TagsBundle\Core\FieldType\Tags\Value as TagFieldValue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
    private InputInterface $input;

    public function __construct(
        private Repository              $repository,
        private TagsService             $tagsService,
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
            ->addOption('field-identifiers', null, InputOption::VALUE_OPTIONAL);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->style = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $query = new Query();
        $query->filter = new Criterion\LogicalAnd([
            new Criterion\ParentLocationId($this->getParentLocation()),
            new Criterion\ContentTypeIdentifier($this->getContentTypes()),
        ]);

        $batchSize = 50;

        $searchResults = $this->repository->getSearchService()->findContent($query);
        $totalResults = $searchResults->totalCount;

        $this->style->newLine();
        $this->style->progressStart($totalResults);

        $this->repository->beginTransaction();

        if ($this->input->getOption('field-identifiers') === null) {
            $fieldIdentifiers = $this->configResolver->getParameter('tag_command_default_field_identifiers', 'ngsite');
        } else {
            $fieldIdentifiers = $this->getFieldIdentifiers();
        }

        for ($offset = 0; $offset < $totalResults; $offset += $batchSize) {
            $query->offset = $offset;
            $query->limit = $batchSize;
            $searchResults = $this->repository->getSearchService()->findContent($query);

            foreach ($searchResults->searchHits as $searchHit) {
                /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $content */
                $content = $searchHit->valueObject;

                foreach ($fieldIdentifiers as $fieldIdentifier) {
                    if ($this->hasField($content, $fieldIdentifier) === false) {
                        continue;
                    }

                    if (!$content->getField($fieldIdentifier)->value instanceof TagFieldValue) {
                        $this->style->error(sprintf('Field with identifier %s must be a type of eztags', $fieldIdentifier));

                        return Command::FAILURE;
                    }

                    $alreadyAssignedTags = $content->getFieldValue($fieldIdentifier)->tags;
                    $tag = $this->getTag();
                    $tagsToAssign = array_filter($alreadyAssignedTags, fn ($alreadyAssignedTag) => $tag->id !== $alreadyAssignedTag->id);
                    $tagsToAssign[] = $tag;

                    $this->repository->sudo(
                        function () use ($content, $fieldIdentifier, $tagsToAssign): void {
                            $contentDraft = $this->repository->getContentService()->createContentDraft($content->contentInfo);
                            $contentUpdateStruct = $this->repository->getContentService()->newContentUpdateStruct();

                            $contentUpdateStruct->setField($fieldIdentifier, new TagFieldValue($tagsToAssign));

                            $this->repository->getContentService()->updateContent($contentDraft->versionInfo, $contentUpdateStruct);
                            $this->repository->getContentService()->publishVersion($contentDraft->versionInfo);
                        }
                    );
                    break;
                }

                $this->style->progressAdvance();
            }
        }

        $this->repository->commit();

        $this->style->progressFinish();
        $this->style->success('Tags assigned successfully');

        return Command::SUCCESS;
    }

    protected function getParentLocation(): int
    {
        $parentLocation = $this->input->getOption('parent-location');

        if (!is_numeric($parentLocation) || (int)$parentLocation < 1) {
            throw new InvalidOptionException(
                sprintf("Argument --parent-location must be an integer > 0, you provided '%s'", $parentLocation),
            );
        }

        return (int)$parentLocation;
    }

    protected function getContentTypes(): array
    {
        return $this->parseCommaDelimited($this->input->getOption('content-types'));
    }

    protected function getTag(): Tag
    {
        return $this->tagsService->loadTag($this->getTagId());
    }

    protected function getFieldIdentifiers(): array
    {
        return $this->parseCommaDelimited($this->input->getOption('field-identifiers'));
    }

    protected function getTagId(): int
    {
        $tagId = $this->input->getOption('tag-id');

        if (!is_numeric($tagId) || (int)$tagId < 1) {
            throw new InvalidOptionException(
                sprintf("Argument --tag-id must be an integer > 0, you provided '%s'", $tagId),
            );
        }

        return (int)$tagId;
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
