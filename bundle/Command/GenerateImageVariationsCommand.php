<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\SPI\Variation\VariationHandler;
use Generator;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_filter;
use function array_keys;
use function array_map;
use function array_unique;
use function array_values;
use function explode;
use function in_array;
use function iterator_to_array;
use function trim;

class GenerateImageVariationsCommand extends Command
{
    private Repository $repository;

    private VariationHandler $variationHandler;

    private TagAwareAdapterInterface $cache;

    private ConfigResolverInterface $configResolver;

    private InputInterface $input;

    private OutputInterface $output;

    private StyleInterface $style;

    public function __construct(
        Repository $repository,
        VariationHandler $variationHandler,
        TagAwareAdapterInterface $cache,
        ConfigResolverInterface $configResolver
    ) {
        $this->repository = $repository;
        $this->variationHandler = $variationHandler;
        $this->cache = $cache;
        $this->configResolver = $configResolver;

        // Parent constructor call is mandatory for commands registered as services
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('ngsite:content:generate-image-variations')
            ->setDescription('Generates image variations for images based on provided filters')
            ->addOption('content-types', null, InputOption::VALUE_OPTIONAL)
            ->addOption('fields', null, InputOption::VALUE_OPTIONAL)
            ->addOption('variations', null, InputOption::VALUE_OPTIONAL)
            ->addOption('subtrees', null, InputOption::VALUE_OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->input = $input;
        $this->output = $output;

        $this->style = new SymfonyStyle($this->input, $this->output);

        $query = $this->getQuery();
        $query->limit = 0;

        $totalCount = $this->repository->sudo(
            function (Repository $repository) use ($query): int {
                $languages = $this->configResolver->getParameter('languages');

                return $repository->getSearchService()->findContentInfo($query, $languages, false)->totalCount ?? 0;
            },
        );

        $query->limit = 50;
        $query->performCount = false;

        $this->style->newLine();
        $this->style->progressStart($totalCount);

        $imageVariations = $this->parseCommaDelimited($this->input->getOption('variations'));
        if (empty($imageVariations)) {
            $imageVariations = array_keys($this->configResolver->getParameter('image_variations'));
        }

        $fields = $this->parseCommaDelimited($this->input->getOption('fields'));

        do {
            $searchHits = $this->repository->sudo(
                function (Repository $repository) use ($query): iterable {
                    $languages = $this->configResolver->getParameter('languages');

                    return $repository->getSearchService()->findContent($query, $languages, false)->searchHits;
                },
            );

            foreach ($searchHits as $searchHit) {
                $this->clearVariationCache($searchHit->valueObject);
                $this->generateVariations($searchHit->valueObject, $imageVariations, $fields);
                $this->style->progressAdvance();
            }

            $query->offset += $query->limit;
        } while ($query->offset < $totalCount);

        $this->style->progressFinish();

        $this->style->success('Generating variations completed successfully');

        return 0;
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

            if (!empty($fields) && !in_array($field->fieldDefIdentifier, $fields, true)) {
                continue;
            }

            if (empty($field->value->uri)) {
                continue;
            }

            foreach ($variations as $variation) {
                $this->variationHandler->getVariation($field, $content->versionInfo, $variation);
            }
        }
    }

    private function getQuery(): Query
    {
        $query = new Query();

        $contentTypes = $this->parseCommaDelimited($this->input->getOption('content-types'));
        $subtrees = $this->parseCommaDelimited($this->input->getOption('subtrees'));

        $criteria = [];

        if (!empty($contentTypes)) {
            $criteria[] = new Criterion\ContentTypeIdentifier($contentTypes);
        }

        if (!empty($subtrees)) {
            $criteria[] = new Criterion\Subtree(iterator_to_array($this->getSubtreePathStrings($subtrees)));
        }

        if (!empty($criteria)) {
            $query->filter = new Criterion\LogicalAnd($criteria);
        }

        return $query;
    }

    private function getSubtreePathStrings(array $subtreeIds): Generator
    {
        foreach ($subtreeIds as $subtreeId) {
            yield $this->repository->sudo(
                static fn (Repository $repository): string => $repository->getLocationService()->loadLocation($subtreeId)->pathString,
            );
        }
    }

    private function parseCommaDelimited(?string $value): array
    {
        $value = trim($value ?? '');

        if (empty($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('trim', explode(',', $value)))));
    }
}
