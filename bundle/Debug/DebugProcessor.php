<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Debug;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

use function call_user_func;
use function in_array;
use function is_callable;

/**
 * This debug processor overrides Symfony built in DebugProcessor to exclude
 * the provided list of channels from Symfony profiler.
 *
 * By default, it excludes "doctrine" and "event" channels, which cause most
 * of the slowdown when rendering the profiler.
 */
final class DebugProcessor implements DebugLoggerInterface
{
    /**
     * @param string[] $excludedChannels
     */
    public function __construct(
        private DebugLoggerInterface $innerProcessor,
        private array $excludedChannels = ['event', 'doctrine'],
    ) {}

    /**
     * @param array<string, mixed> $record
     *
     * @return array<string, mixed>
     */
    public function __invoke(array $record): array
    {
        $channel = $record['channel'] ?? '';
        if (is_callable($this->innerProcessor) && !in_array($channel, $this->excludedChannels, true)) {
            call_user_func($this->innerProcessor, $record);
        }

        return $record;
    }

    /**
     * @return string[]
     */
    public function getLogs(?Request $request = null): array
    {
        return $this->innerProcessor->getLogs();
    }

    public function countErrors(?Request $request = null): int
    {
        return $this->innerProcessor->countErrors();
    }

    public function clear(): void
    {
        $this->innerProcessor->clear();
    }
}
