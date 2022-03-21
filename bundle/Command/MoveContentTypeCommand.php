<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command;

use Exception;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeGroup;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use function array_map;
use function ctype_digit;
use function explode;

final class MoveContentTypeCommand extends Command
{
    protected static $defaultDescription = 'Assigns ContentType(s) to a single ContentTypeGroup';

    private Repository $repository;
    private ContentTypeService $contentTypeService;

    public function __construct(
        Repository $repository,
        ContentTypeService $contentTypeService
    ) {
        parent::__construct();

        $this->repository = $repository;
        $this->contentTypeService = $contentTypeService;
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(
            '<info>This command assigns given ContentType(s) to the given ContentTypeGroup and unassigns them from all other ContentTypeGroups.</info>',
        );

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Continue with this action? (Y/n) ', true);

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<info>Aborted.</info>');

            return Command::SUCCESS;
        }

        $types = $input->getArgument('types');
        $contentTypeIdentifiersOrIds = array_map('trim', explode(',', $types));
        $contentTypeGroupIdentifierOrId = $input->getArgument('group');

        $newContentTypeGroup = $this->loadContentTypeGroup($contentTypeGroupIdentifierOrId);

        foreach ($contentTypeIdentifiersOrIds as $contentTypeIdentifierOrId) {
            $contentType = $this->loadContentType($contentTypeIdentifierOrId);

            $this->repository->beginTransaction();

            try {
                $this->assignToSingleGroup($contentType, $newContentTypeGroup);
            } catch (Exception $exception) {
                $output->writeln('<error>' . $exception->getMessage() . '</error>');

                $this->repository->rollback();

                continue;
            }

            $this->repository->commit();
        }

        $output->writeln('<info>Done.</info>');

        return Command::SUCCESS;
    }

    /**
     * @param string|int $identifierOrId
     */
    private function loadContentTypeGroup($identifierOrId): ContentTypeGroup
    {
        if (ctype_digit($identifierOrId)) {
            return $this->repository->sudo(
                fn (): ContentTypeGroup => $this->contentTypeService->loadContentTypeGroup((int) $identifierOrId),
            );
        }

        return $this->repository->sudo(
            fn (): ContentTypeGroup => $this->contentTypeService->loadContentTypeGroupByIdentifier($identifierOrId),
        );
    }

    /**
     * @param string|int $identifierOrId
     */
    private function loadContentType($identifierOrId): ContentType
    {
        if (ctype_digit($identifierOrId)) {
            return $this->repository->sudo(
                fn (): ContentType => $this->contentTypeService->loadContentType((int) $identifierOrId),
            );
        }

        return $this->repository->sudo(
            fn (): ContentType => $this->contentTypeService->loadContentTypeByIdentifier($identifierOrId),
        );
    }

    private function assignToSingleGroup(ContentType $contentType, ContentTypeGroup $newContentTypeGroup): void
    {
        try {
            $this->repository->sudo(
                function () use ($newContentTypeGroup, $contentType) {
                    $this->contentTypeService->assignContentTypeGroup($contentType, $newContentTypeGroup);
                },
            );
        } catch (InvalidArgumentException $exception) {
            // ContentType is already assigned to the given ContentTypeGroup, do nothing
        }

        foreach ($contentType->contentTypeGroups as $contentTypeGroup) {
            if ($contentTypeGroup->id === $newContentTypeGroup->id) {
                continue;
            }

            $this->repository->sudo(
                function () use ($contentType, $contentTypeGroup) {
                    $this->contentTypeService->unassignContentTypeGroup($contentType, $contentTypeGroup);
                },
            );
        }
    }
}
