<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function array_map;
use function explode;

final class UpdateContentAlwaysAvailableCommand extends Command
{
    protected static $defaultDescription = 'Update always-available state for the given Content item(s)';

    private Repository $repository;

    private ContentService $contentService;

    private StyleInterface $style;

    public function __construct(
        Repository $repository,
        ContentService $contentService
    ) {
        parent::__construct();

        $this->repository = $repository;
        $this->contentService = $contentService;
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'content-ids',
                InputArgument::REQUIRED,
                'Content IDs (comma delimited)',
            )
            ->addArgument(
                'always-available',
                InputArgument::OPTIONAL,
                'Always-available state (true/false or 1/0)',
                true,
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->style = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->style->info('This command updates Content item(s) always-available flag.');

        if (!$this->style->confirm('Continue with this action?', true)) {
            $this->style->error('Aborted.');

            return Command::FAILURE;
        }

        $types = $input->getArgument('content-ids');
        $contentIds = array_map('intval', array_map('trim', explode(',', $types)));
        $alwaysAvailableState = $this->resolveAlwaysAvailableState($input->getArgument('always-available'));

        foreach ($contentIds as $contentId) {
            $contentInfo = $this->contentService->loadContentInfo($contentId);

            $struct = $this->contentService->newContentMetadataUpdateStruct();
            $struct->alwaysAvailable = $alwaysAvailableState;

            $this->repository->sudo(
                function () use ($contentInfo, $struct) {
                    $this->contentService->updateContentMetadata($contentInfo, $struct);
                },
            );
        }

        $this->style->info('Done.');

        return Command::SUCCESS;
    }

    private function resolveAlwaysAvailableState($argument): bool
    {
        if ($argument === 'false') {
            return false;
        }

        return (bool) $argument;
    }
}
