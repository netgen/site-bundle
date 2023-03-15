<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command;

use Generator;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Contracts\Core\Variation\VariationHandler;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use function array_filter;
use function array_keys;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function explode;
use function in_array;
use function iterator_to_array;
use function sprintf;
use function trim;

final class GenerateImageVariationsCommand extends Command
{
    private SymfonyStyle $style;

    public function __construct(
        private Repository $repository,
        private VariationHandler $variationHandler,
        private TagAwareAdapterInterface $cache,
        private ConfigResolverInterface $configResolver,
    ) {
        // Parent constructor call is mandatory for commands registered as services
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Generates image variations for images based on provided filters')
            ->addOption('content-types', null, InputOption::VALUE_OPTIONAL)
            ->addOption('fields', null, InputOption::VALUE_OPTIONAL)
            ->addOption('variations', null, InputOption::VALUE_OPTIONAL)
            ->addOption('subtrees', null, InputOption::VALUE_OPTIONAL);
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->style = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $query = $this->getQuery($input);
        $query->limit = 0;

        $totalCount = $this->repository->sudo(
            function () use ($query): int {
                $languages = $this->configResolver->getParameter('languages');

                return $this->repository->getSearchService()->findContentInfo($query, $languages, false)->totalCount ?? 0;
            },
        );

        $query->limit = 50;
        $query->performCount = false;

        $this->style->newLine();
        $this->style->progressStart($totalCount);

        $imageVariations = $this->parseCommaDelimited($input->getOption('variations'));
        if (count($imageVariations) === 0) {
            $imageVariations = array_keys($this->configResolver->getParameter('image_variations'));
        }

        $fields = $this->parseCommaDelimited($input->getOption('fields'));

        do {
            $searchHits = $this->repository->sudo(
                function () use ($query): iterable {
                    $languages = $this->configResolver->getParameter('languages');

                    return $this->repository->getSearchService()->findContent($query, $languages, false)->searchHits;
                },
            );

            /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content[] $contentItems */
            $contentItems = array_map(
                static fn (SearchHit $searchHit): ValueObject => $searchHit->valueObject,
                $searchHits,
            );

            foreach ($contentItems as $content) {
                $this->clearVariationCache($content);
                $this->generateVariations($content, $imageVariations, $fields);
                $this->style->progressAdvance();
            }

            $query->offset += $query->limit;
        } while ($query->offset < $totalCount);

        $this->style->progressFinish();

        $this->style->success('Generating variations completed successfully');

        return Command::SUCCESS;
    }

    private function clearVariationCache(Content $content): void
    {
        $this->cache->invalidateTags(['image-variation-content-' . $content->id]);
    }

    private function generateVariations(Content $content, array $variations, array $fields): void
    {
        foreach ($content->getFields() as $field) {
            if ($field->fieldTypeIdentifier !== 'ezimage') {
                continue;
            }

            if (count($fields) > 0 && !in_array($field->fieldDefIdentifier, $fields, true)) {
                continue;
            }

            if (($field->value->uri ?? '') === '') {
                continue;
            }

            foreach ($variations as $variation) {
                try {
                    $this->variationHandler->getVariation($field, $content->versionInfo, $variation);
                } catch (Throwable $throwable) {
                    $this->style->error(sprintf('Could not get variation: %s', $throwable->getMessage()));
                }
            }
        }
    }

    private function getQuery(InputInterface $input): Query
    {
        $query = new Query();

        $contentTypes = $this->parseCommaDelimited($input->getOption('content-types'));
        $subtrees = $this->parseCommaDelimited($input->getOption('subtrees'));

        $criteria = [];

        if (count($contentTypes) > 0) {
            $criteria[] = new Criterion\ContentTypeIdentifier($contentTypes);
        }

        if (count($subtrees) > 0) {
            $criteria[] = new Criterion\Subtree(iterator_to_array($this->getSubtreePathStrings($subtrees)));
        }

        if (count($criteria) > 0) {
            $query->filter = new Criterion\LogicalAnd($criteria);
        }

        return $query;
    }

    private function getSubtreePathStrings(array $subtreeIds): Generator
    {
        foreach ($subtreeIds as $subtreeId) {
            yield $this->repository->sudo(
                fn (): string => $this->repository->getLocationService()->loadLocation($subtreeId)->pathString,
            );
        }
    }

    private function parseCommaDelimited(?string $value): array
    {
        $value = trim($value ?? '');

        if ($value === '') {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('trim', explode(',', $value)))));
    }
}
