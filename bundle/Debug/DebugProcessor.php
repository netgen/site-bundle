<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Debug;

use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

use function call_user_func;
use function in_array;

/**
 * This debug processor overrides Symfony built in DebugProcessor to exclude
 * the provided list of channels from Symfony profiler.
 *
 * By default, it excludes "doctrine" and "event" channels, which cause most
 * of the slowdown when rendering the profiler.
 */
class DebugProcessor implements DebugLoggerInterface
{
    protected DebugLoggerInterface $innerProcessor;

    protected array $excludedChannels;

    public function __construct(
        DebugLoggerInterface $innerProcessor,
        array $excludedChannels = ['event', 'doctrine']
    ) {
        $this->innerProcessor = $innerProcessor;
        $this->excludedChannels = $excludedChannels;
    }

    public function __invoke(array $record): array
    {
        $channel = $record['channel'] ?? '';
        if (!in_array($channel, $this->excludedChannels, true)) {
            call_user_func($this->innerProcessor, $record);
        }

        return $record;
    }

    public function getLogs(): array
    {
        return $this->innerProcessor->getLogs();
    }

    public function countErrors(): int
    {
        return $this->innerProcessor->countErrors();
    }

    public function clear(): void
    {
        $this->innerProcessor->clear();
    }
}
