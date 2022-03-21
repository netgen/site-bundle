<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class UpdateContentAlwaysAvailableCommand extends Command
{
    protected static $defaultDescription = 'Update always-available state for the given Content item(s)';

    private Repository $repository;
    private ContentService $contentService;

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
                'content_ids',
                InputArgument::REQUIRED,
                'Content IDs (comma delimited)',
            )
            ->addArgument(
                'always-available',
                InputArgument::OPTIONAL,
                'Always-available state (true/false or 1/0)',
                true
            );
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(
            '<info>This command updates Content item(s) always-available flag.</info>'
        );

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Continue with this action? (Y/n) ', true);

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<info>Aborted.</info>');

            return Command::SUCCESS;
        }

        $types = $input->getArgument('content_ids');
        $contentIds = array_map('intval', array_map('trim', explode(',', $types)));
        $alwaysAvailableState = $this->resolveAlwaysAvailableState($input->getArgument('always-available'));

        foreach ($contentIds as $contentId) {
            $contentInfo = $this->repository->sudo(
                function () use ($contentId) {
                    return $this->contentService->loadContentInfo($contentId);
                }
            );

            $struct = $this->contentService->newContentMetadataUpdateStruct();
            $struct->alwaysAvailable = $alwaysAvailableState;

            $this->repository->sudo(
                function () use ($contentInfo, $struct) {
                    $this->contentService->updateContentMetadata($contentInfo, $struct);
                }
            );
        }

        $output->writeln('<info>Done.</info>');

        return self::SUCCESS;
    }

    private function resolveAlwaysAvailableState($argument): bool
    {
        if ($argument === 'false') {
            return false;
        }

        return (bool) $argument;
    }
}
