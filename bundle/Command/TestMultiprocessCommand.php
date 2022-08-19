<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command;

use Generator;
use Netgen\Bundle\SiteBundle\Command\MultiprocessCommand\Items;

use function array_chunk;
use function array_keys;
use function array_merge;
use function ceil;
use function count;
use function file_put_contents;
use function getmypid;
use function is_array;
use function random_int;
use function sleep;

use const COUNT_RECURSIVE;
use const FILE_APPEND;

class TestMultiprocessCommand extends BaseMultiprocessCommand
{
    private array $data = [
        '1-1' => [
            '2-1' => [
                '3-1' => true,
                '3-2' => true,
            ],
            '2-2' => [
                '3-3' => [
                    '4-1' => [
                        '5-1' => true,
                        '5-2' => true,
                        '5-3' => true,
                        '5-4' => true,
                        '5-5' => true,
                        '5-6' => true,
                        '5-7' => true,
                        '5-8' => true,
                        '5-9' => true,
                        '5-10' => true,
                        '5-11' => true,
                        '5-12' => true,
                        '5-13' => true,
                        '5-14' => true,
                        '5-15' => true,
                        '5-16' => true,
                    ],
                ],
                '3-4' => [
                    '4-2' => true,
                    '4-3' => true,
                    '4-4' => true,
                    '4-5' => true,
                    '4-6' => true,
                    '4-7' => true,
                    '4-8' => true,
                ],
                '3-5' => [
                    '4-9' => true,
                    '4-10' => true,
                    '4-11' => true,
                    '4-12' => true,
                    '4-13' => true,
                    '4-14' => true,
                    '4-15' => true,
                    '4-16' => true,
                ],
            ],
            '2-3' => [
                '3-6' => true,
                '3-7' => true,
            ],
        ],
        '1-2' => [
            '2-4' => true,
            '2-5' => true,
        ],
        '1-3' => [
            '2-6' => true,
            '2-7' => true,
        ],
    ];

    protected function getItemsGenerator(int $limit): Generator
    {
        $maxDepth = $this->getDepth($this->data);

        for ($depth = $maxDepth; $depth >= 1; --$depth) {
            $allItems = $this->getFromDepth($this->data, $depth);
            $itemsChunked = array_chunk($allItems, $limit);

            foreach ($itemsChunked as $items) {
                yield new Items($items, $depth);
            }
        }
    }

    protected function getCount(): int
    {
        return count($this->data, COUNT_RECURSIVE);
    }

    protected function getNumberOfIterations(int $count, int $limit): int
    {
        $maxDepth = $this->getDepth($this->data);
        $iterationCount = 0;

        for ($depth = $maxDepth; $depth >= 1; --$depth) {
            $allItems = $this->getFromDepth($this->data, $depth);
            $iterationCount += (int) ceil(count($allItems) / $limit);
        }

        return $iterationCount;
    }

    /**
     * @param mixed $item
     *
     * @throws \Exception
     */
    protected function process($item): void
    {
        sleep(random_int(1, 3));
        file_put_contents('/Users/petar/out.txt', getmypid() . ': ' . $item . "\n", FILE_APPEND);
    }

    private function getDepth(array $array): int
    {
        $maxDepth = 1;

        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = $this->getDepth($value) + 1;

                if ($depth > $maxDepth) {
                    $maxDepth = $depth;
                }
            }
        }

        return $maxDepth;
    }

    private function getFromDepth(array $array, int $depth): array
    {
        if ($depth === 1) {
            return array_keys($array);
        }

        $grouped = [[]];

        foreach ($array as $value) {
            if (is_array($value)) {
                $grouped[] = $this->getFromDepth($value, $depth - 1);
            }
        }

        return array_merge(...$grouped);
    }
}
