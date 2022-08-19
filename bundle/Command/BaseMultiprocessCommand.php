<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command;

use Generator;
use Netgen\Bundle\SiteBundle\Command\MultiprocessCommand\Items;
use Psr\Log\LoggerInterface;
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
use function pclose;
use function popen;
use function preg_match;
use function preg_match_all;
use function spl_object_id;
use function sprintf;
use function stream_get_contents;
use function usleep;

use const DIRECTORY_SEPARATOR;

abstract class BaseMultiprocessCommand extends Command
{
    protected ?string $phpPath;
    protected string $projectDir;
    protected LoggerInterface $logger;
    protected InputInterface $input;
    protected SymfonyStyle $symfonyStyle;
    protected ProgressBar $progressBar;

    public function __construct(
        string $projectDir,
        LoggerInterface $logger,
        ?string $phpPath = null
    ) {
        $this->projectDir = $projectDir;
        $this->logger = $logger;
        $this->phpPath = $phpPath;

        parent::__construct();
    }

    abstract protected function getCount(): int;

    abstract protected function getItemsGenerator(int $limit): Generator;

    abstract protected function process($item): void;

    protected function configure(): void
    {
        $this->addOption(
            'limit',
            null,
            InputOption::VALUE_OPTIONAL,
            'Number of items to process in a single iteration',
            50,
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
        $count = $this->getCount();

        if ($count < 1) {
            $this->symfonyStyle->error('Nothing to process, aborting.');

            return 1;
        }

        $limit = $this->getLimit();
        $iterations = $this->getNumberOfIterations($count, $limit);
        $processCount = $this->getMaxNumberOfProcesses();
        $processCount = min($iterations, $processCount);
        $processMessage = $processCount > 1
            ? sprintf('using up to %s parallel child processes', $processCount)
            : 'using a single (current) process';

        $this->progressBar = $this->symfonyStyle->createProgressBar();
        $this->progressBar->setFormat('very_verbose');

        if ($processCount > 1) {
            ProgressBar::setFormatDefinition(
                'very_verbose',
                ProgressBar::getFormatDefinition('very_verbose') . '%process_count%',
            );
            $this->progressBar->setMessage(' (...)', 'process_count');
            $this->symfonyStyle->writeln(
                sprintf(
                    'Processing for %s items across %s iteration(s), %s:',
                    $count,
                    $iterations,
                    $processMessage,
                ),
            );
            $this->progressBar->start($iterations);

            $this->dispatch($processCount, $limit);
        } else {
            $this->symfonyStyle->writeln(sprintf('Processing %s items, %s:', $count, $processMessage));
            $this->progressBar->start($count);
            $generator = $this->internalGetItemGenerator($limit);

            /** @var \Netgen\Bundle\SiteBundle\Command\MultiprocessCommand\Items $items */
            foreach ($generator as $items) {
                foreach ($items->getItems() as $item) {
                    $this->process($item);
                    $this->progressBar->advance();
                }
            }
        }

        $this->progressBar->setMessage('', 'process_count');
        $this->progressBar->finish();
        $this->symfonyStyle->newLine(2);
        $this->symfonyStyle->success('Finished processing');
        $this->progressBar->clear();

        return 0;
    }

    protected function dispatch(int $processCount, int $limit): void
    {
        $generator = $this->internalGetItemGenerator($limit);

        /** @var \Symfony\Component\Process\Process[]|null[] $processes */
        $processes = array_fill(0, $processCount, null);
        $processDepthMap = [];
        $items = null;

        do {
            $activeProcessCount = 0;
            foreach ($processes as $process) {
                if ($process !== null && $process->isRunning()) {
                    ++$activeProcessCount;
                }
            }

            $this->progressBar->setMessage(
                ' (processes: ' . $activeProcessCount . ')',
                'process_count',
            );

            if ($items === null && $generator->valid()) {
                /** @var ?\Netgen\Bundle\SiteBundle\Command\MultiprocessCommand\Items $items */
                $items = $generator->current();
                $generator->next();
            }

            foreach ($processes as $key => $process) {
                if ($process !== null && $process->isRunning()) {
                    continue;
                }

                if ($process !== null) {
                    $this->progressBar->advance();

                    if (!$process->isSuccessful()) {
                        $this->logger->error(
                            sprintf(
                                'Child process returned: %s - %s',
                                $process->getExitCodeText(),
                                $process->getOutput(),
                            ),
                        );
                    }

                    unset($processDepthMap[spl_object_id($process)]);
                    $processes[$key] = null;
                }

                if ($items === null && !$generator->valid()) {
                    unset($processes[$key]);

                    continue;
                }

                if ($items === null) {
                    break;
                }

                if ($this->shouldWait($processDepthMap, $processes, $items)) {
                    break;
                }

                $processes[$key] = $this->getProcess($items);
                $processes[$key]->start();
                $processDepthMap[spl_object_id($processes[$key])] = $items->getDepth();

                $items = null;

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

            yield new Items($items);

            return;
        }

        yield from $this->getItemsGenerator($limit);
    }

    protected function shouldWait(array $processDepthMap, array $processes, Items $items): bool
    {
        foreach ($processes as $process) {
            if ($process === null || !$process->isRunning()) {
                continue;
            }

            $processDepth = $processDepthMap[spl_object_id($process)] ?? null;

            if ($processDepth === null) {
                continue;
            }

            if ($processDepth > $items->getDepth()) {
                return true;
            }
        }

        return false;
    }

    protected function getProcess(Items $items): Process
    {
        $arguments = [
            $this->getPhpPath(),
            sprintf('%s/bin/console', $this->projectDir),
            $this->getName(),
            '--processes=1',
            '--items=' . implode(',', $items->getItems()),
        ];

        foreach ($this->input->getOptions() as $key => $value) {
            if (in_array($key, ['processes', 'items'], true)) {
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

    /**
     * @return string
     */
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

    /**
     * @return int
     */
    protected function getNumberOfCPUCores(): int
    {
        $cores = 1;

        if (is_file('/proc/cpuinfo')) {
            // Linux (and potentially Windows with linux sub systems)
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuinfo, $matches);
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
