<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command;

use Generator;
use Netgen\Bundle\SiteBundle\Command\MultiprocessCommand\ItemList;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

use function array_fill;
use function ceil;
use function count;
use function explode;
use function fgets;
use function file_get_contents;
use function implode;
use function in_array;
use function is_file;
use function is_numeric;
use function min;
use function number_format;
use function pclose;
use function popen;
use function preg_match;
use function preg_match_all;
use function spl_object_id;
use function sprintf;
use function stream_get_contents;
use function time;
use function usleep;

use const DIRECTORY_SEPARATOR;

abstract class BaseMultiprocessCommand extends Command
{
    protected InputInterface $input;

    protected SymfonyStyle $symfonyStyle;

    protected ProgressBar $progressBar;

    public function __construct(
        protected readonly string $projectDir,
        protected readonly LoggerInterface $logger = new NullLogger(),
        protected ?string $phpPath = null,
    ) {
        parent::__construct();
    }

    abstract protected function getTotalCount(): int;

    abstract protected function getItemsGenerator(int $limit): Generator;

    abstract protected function process(ItemList $itemList): void;

    protected function configure(): void
    {
        $this->addOption(
            'limit',
            null,
            InputOption::VALUE_OPTIONAL,
            'Number of items to process in a single iteration',
            512,
        );

        $this->addOption(
            'processes',
            null,
            InputOption::VALUE_OPTIONAL,
            'Maximum number of processes to run in parallel. By default it will use the number of CPU cores minus 1.',
            'auto',
        );

        $this->addOption(
            'items',
            null,
            InputOption::VALUE_OPTIONAL,
            'Comma-separated list of item identifiers',
        );

        $this->addOption(
            'master',
            null,
            InputOption::VALUE_OPTIONAL,
            'Do not use directly, this is an internal option used by sub-process dispatcher',
            'yes',
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->input = $input;
        $this->symfonyStyle = new SymfonyStyle($input, $output);
    }

    protected function getLimit(): int
    {
        $limit = $this->input->getOption('limit');

        if (!is_numeric($limit) || (int) $limit < 1) {
            throw new InvalidOptionException(
                sprintf("Option --limit value must be an integer > 0, you provided '%s'", $limit),
            );
        }

        return (int) $limit;
    }

    /**
     * Default implementation, if you process items.
     */
    protected function getNumberOfIterations(int $count, int $limit): int
    {
        return (int) ceil($count / $limit);
    }

    protected function getMaxNumberOfProcesses(): int
    {
        $processes = $this->input->getOption('processes');

        return $processes === 'auto'
            ? $this->getNumberOfCPUCores() - 1
            : (int) $processes;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = $this->getExecutionCount();

        if ($count < 1) {
            $this->symfonyStyle->error('Nothing to process, aborting.');

            return Command::FAILURE;
        }

        $limit = $this->getLimit();
        $iterations = $this->getNumberOfIterations($count, $limit);
        $processCount = $this->getMaxNumberOfProcesses();
        $processCount = min($iterations, $processCount);

        $this->progressBar = $this->symfonyStyle->createProgressBar();
        $this->progressBar->setProgressCharacter('>');
        $this->progressBar->setEmptyBarCharacter(' ');
        $this->progressBar->setBarCharacter('=');
        $this->progressBar->setFormat('very_verbose');

        if ($processCount > 1) {
            ProgressBar::setFormatDefinition(
                'very_verbose',
                ProgressBar::getFormatDefinition('debug') . '%process_count%',
            );
            $this->progressBar->setMessage(' (...)', 'process_count');
            $this->symfonyStyle->writeln(
                sprintf(
                    'Processing %s item(s) in chunks of %s across %s iteration(s), using up to %s parallel child processes...',
                    number_format($count),
                    $limit,
                    $iterations,
                    $processCount,
                ),
            );
            $this->symfonyStyle->newLine();
            $this->progressBar->start($count);

            $this->dispatch($processCount, $limit);
        } else {
            $this->symfonyStyle->writeln(
                sprintf(
                    'Processing %s item(s) in chunks of %s across %s iteration(s), using a single (current) process...',
                    number_format($count),
                    $limit,
                    $iterations,
                ),
            );
            $this->symfonyStyle->newLine();
            $this->progressBar->start($count);
            $generator = $this->internalGetItemGenerator($limit);

            /** @var \Netgen\Bundle\SiteBundle\Command\MultiprocessCommand\ItemList $itemList */
            foreach ($generator as $itemList) {
                $this->process($itemList);
                $this->progressBar->advance($itemList->getCount());
            }
        }

        $this->progressBar->setMessage('', 'process_count');
        $this->progressBar->finish();
        $this->symfonyStyle->newLine(2);
        $this->symfonyStyle->success('Done');
        $this->progressBar->clear();

        return Command::SUCCESS;
    }

    protected function getExecutionCount(): int
    {
        $items = $this->input->getOption('items');

        if ($items !== null) {
            $items = explode(',', $items);

            return count($items);
        }

        return $this->getTotalCount();
    }

    protected function dispatch(int $processCount, int $limit): void
    {
        $generator = $this->internalGetItemGenerator($limit);

        /** @var \Symfony\Component\Process\Process[]|null[] $processes */
        $processes = array_fill(0, $processCount, null);
        $processDepthMap = [];
        $itemList = null;
        $itemCount = 0;
        $timestamp = time();

        do {
            $activeProcessCount = 0;
            foreach ($processes as $process) {
                if ($process !== null && $process->isRunning()) {
                    ++$activeProcessCount;
                }
            }

            $this->progressBar->setMessage(
                sprintf(' (processes: %d)', $activeProcessCount),
                'process_count',
            );

            $currentTimestamp = time();

            if ($currentTimestamp > $timestamp) {
                $timestamp = $currentTimestamp;
                $this->progressBar->display();
            }

            if ($itemList === null && $generator->valid()) {
                /** @var ?\Netgen\Bundle\SiteBundle\Command\MultiprocessCommand\ItemList $itemList */
                $itemList = $generator->current();
                $itemCount = $itemList->getCount();
                $generator->next();
            }

            foreach ($processes as $key => $process) {
                if ($process !== null && $process->isRunning()) {
                    continue;
                }

                if ($process !== null) {
                    $this->progressBar->advance($itemCount);

                    if (!$process->isSuccessful()) {
                        $this->logger->error(
                            sprintf(
                                'Child process returned: %s - %s',
                                $process->getExitCodeText(),
                                $process->getErrorOutput(),
                            ),
                        );
                    }

                    unset($processDepthMap[spl_object_id($process)]);
                    $processes[$key] = null;
                }

                if ($itemList === null && !$generator->valid()) {
                    unset($processes[$key]);

                    continue;
                }

                if ($itemList === null) {
                    break;
                }

                if ($this->shouldWait($processDepthMap, $processes, $itemList)) {
                    break;
                }

                $processes[$key] = $this->getSubProcess($itemList);
                $processes[$key]->start();
                $processDepthMap[spl_object_id($processes[$key])] = $itemList->getDepth();

                $itemList = null;

                if ($generator->valid()) {
                    break;
                }
            }

            usleep(100000);
        } while (!empty($processes));
    }

    protected function internalGetItemGenerator(int $limit): Generator
    {
        if ($items = $this->input->getOption('items')) {
            $items = explode(',', $items);

            yield new ItemList($items);

            return;
        }

        yield from $this->getItemsGenerator($limit);
    }

    protected function shouldWait(array $processDepthMap, array $processes, ItemList $itemList): bool
    {
        foreach ($processes as $process) {
            if ($process === null || !$process->isRunning()) {
                continue;
            }

            $processDepth = $processDepthMap[spl_object_id($process)] ?? null;

            if ($processDepth === null) {
                continue;
            }

            if ($processDepth > $itemList->getDepth()) {
                return true;
            }
        }

        return false;
    }

    protected function getSubProcess(ItemList $itemList): Process
    {
        $arguments = [
            $this->getPhpPath(),
            sprintf('%s/bin/console', $this->projectDir),
            $this->getName(),
            '--processes=1',
            '--master=no',
            '--items=' . implode(',', $itemList->getItems()),
        ];

        foreach ($this->input->getOptions() as $key => $value) {
            if (in_array($key, ['processes', 'master', 'items'], true)) {
                continue;
            }

            if ($value === true) {
                $arguments[] = '--' . $key;

                continue;
            }

            if ($value === false || $value === null) {
                continue;
            }

            $arguments[] = '--' . $key . '=' . $value;
        }

        $process = new Process($arguments);
        $process->setTimeout(null);

        return $process;
    }

    protected function getPhpPath(): string
    {
        if ($this->phpPath) {
            return $this->phpPath;
        }

        $phpFinder = new PhpExecutableFinder();
        $this->phpPath = $phpFinder->find();

        if (!$this->phpPath) {
            throw new RuntimeException('PHP executable could not be found');
        }

        return $this->phpPath;
    }

    protected function getNumberOfCPUCores(): int
    {
        $cores = 1;

        if (is_file('/proc/cpuinfo')) {
            // Linux (and potentially Windows with linux sub systems)
            $cpuInfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuInfo, $matches);
            $cores = count($matches[0]);
        } elseif (DIRECTORY_SEPARATOR === '\\') {
            // Windows
            if (($process = @popen('wmic cpu get NumberOfCores', 'rb')) !== false) {
                fgets($process);
                $cores = (int) fgets($process);
                pclose($process);
            }
        } elseif (($process = @popen('sysctl -a', 'rb')) !== false) {
            // *nix (Linux, BSD and Mac)
            $output = stream_get_contents($process);
            if (preg_match('/hw.ncpu: (\d+)/', $output, $matches)) {
                $cores = (int) $matches[1];
            }
            pclose($process);
        }

        return $cores;
    }
}
