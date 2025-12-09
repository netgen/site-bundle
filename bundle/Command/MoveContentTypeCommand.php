<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command;

use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeGroup;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use function array_map;
use function explode;
use function is_numeric;

final class MoveContentTypeCommand extends Command
{
    protected static $defaultDescription = 'Assigns content type(s) to a single content type group';

    private SymfonyStyle $style;

    public function __construct(
        private Repository $repository,
        private ContentTypeService $contentTypeService,
    ) {
        // Parent constructor call is mandatory for commands registered as services
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'types',
                InputArgument::REQUIRED,
                'ContentType identifiers or IDs (comma delimited)',
            )
            ->addArgument(
                'group',
                InputArgument::REQUIRED,
                'ContentTypeGroup identifier or ID',
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->style = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->style->info('This command assigns given content type(s) to the given content type group and un-assigns them from all other content type groups.');

        if (!$this->style->confirm('Continue with this action?')) {
            $this->style->error('Aborted.');

            return Command::FAILURE;
        }

        $types = $input->getArgument('types');
        $contentTypeIdentifiersOrIds = array_map('trim', explode(',', $types));
        $contentTypeGroupIdentifierOrId = (string) $input->getArgument('group');

        $newContentTypeGroup = $this->loadContentTypeGroup($contentTypeGroupIdentifierOrId);

        foreach ($contentTypeIdentifiersOrIds as $contentTypeIdentifierOrId) {
            $contentType = $this->loadContentType($contentTypeIdentifierOrId);

            $this->repository->beginTransaction();

            try {
                $this->assignToSingleGroup($contentType, $newContentTypeGroup);
            } catch (Throwable $t) {
                $this->style->error($t->getMessage());

                $this->repository->rollback();

                continue;
            }

            $this->repository->commit();
        }

        $this->style->info('Done.');

        return Command::SUCCESS;
    }

    private function loadContentTypeGroup(string $identifierOrId): ContentTypeGroup
    {
        if (is_numeric($identifierOrId)) {
            return $this->contentTypeService->loadContentTypeGroup((int) $identifierOrId);
        }

        return $this->contentTypeService->loadContentTypeGroupByIdentifier($identifierOrId);
    }

    private function loadContentType(string $identifierOrId): ContentType
    {
        if (is_numeric($identifierOrId)) {
            return $this->contentTypeService->loadContentType((int) $identifierOrId);
        }

        return $this->contentTypeService->loadContentTypeByIdentifier($identifierOrId);
    }

    private function assignToSingleGroup(ContentType $contentType, ContentTypeGroup $newContentTypeGroup): void
    {
        try {
            $this->repository->sudo(
                function (Repository $repository) use ($newContentTypeGroup, $contentType): void {
                    $this->contentTypeService->assignContentTypeGroup($contentType, $newContentTypeGroup);
                },
            );
        } catch (InvalidArgumentException) {
            // Content type is already assigned to the given content type group, do nothing
        }

        foreach ($contentType->contentTypeGroups as $contentTypeGroup) {
            if ($contentTypeGroup->id === $newContentTypeGroup->id) {
                continue;
            }

            $this->repository->sudo(
                function (Repository $repository) use ($contentType, $contentTypeGroup): void {
                    $this->contentTypeService->unassignContentTypeGroup($contentType, $contentTypeGroup);
                },
            );
        }
    }
}
