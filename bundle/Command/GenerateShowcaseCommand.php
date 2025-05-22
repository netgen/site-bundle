<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Locale\LocaleConverterInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\View\ViewManagerInterface;
use Netgen\Bundle\IbexaSiteApiBundle\NamedObject\Provider;
use Netgen\IbexaSiteApi\API\FilterService;
use Netgen\IbexaSiteApi\API\Values\Content;
use Netgen\IbexaSiteApi\API\Values\Location;
use Netgen\IbexaSiteApi\Core\Traits\SearchResultExtractorTrait;
use Netgen\Layouts\API\Service\BlockService;
use Netgen\Layouts\API\Service\CollectionService;
use Netgen\Layouts\API\Service\LayoutResolverService;
use Netgen\Layouts\API\Service\LayoutService;
use Netgen\Layouts\API\Service\TransactionService;
use Netgen\Layouts\API\Values\Block\Block;
use Netgen\Layouts\API\Values\Layout\Layout;
use Netgen\Layouts\API\Values\Value;
use Netgen\Layouts\Block\BlockDefinitionInterface;
use Netgen\Layouts\Block\Registry\BlockDefinitionRegistry;
use Netgen\Layouts\Block\Registry\BlockTypeRegistry;
use Netgen\Layouts\Collection\Registry\ItemDefinitionRegistry;
use Netgen\Layouts\Ibexa\Block\BlockDefinition\Handler\ComponentHandler;
use Netgen\Layouts\Layout\Registry\LayoutTypeRegistry;
use Netgen\Layouts\Parameters\ParameterDefinition;
use Netgen\Layouts\Parameters\ParameterDefinitionCollectionInterface;
use Netgen\Layouts\Parameters\ParameterType\BooleanType;
use Netgen\Layouts\Parameters\ParameterType\ChoiceType;
use Netgen\Layouts\Parameters\ParameterType\Compound\BooleanType as CompoundBooleanType;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_column;
use function array_keys;
use function array_map;
use function array_unique;
use function array_values;
use function implode;
use function in_array;
use function sort;
use function sprintf;
use function str_replace;
use function var_export;

final class GenerateShowcaseCommand extends Command
{
    use SearchResultExtractorTrait;

    private SymfonyStyle $style;

    public function __construct(
        private Provider $namedObjectProvider,
        private FilterService $filterService,
        private Repository $repository,
        private LayoutResolverService $layoutResolverService,
        private LayoutService $layoutService,
        private BlockService $blockService,
        private CollectionService $collectionService,
        private TransactionService $transactionService,
        private LayoutTypeRegistry $layoutTypeRegistry,
        private BlockDefinitionRegistry $blockDefinitionRegistry,
        private BlockTypeRegistry $blockTypeRegistry,
        private ItemDefinitionRegistry $itemDefinitionRegistry,
        private ConfigResolverInterface $configResolver,
        private LocaleConverterInterface $localeConverter,
        private SiteAccess $siteAccess,
        private Connection $connection,
    ) {
        // Parent constructor call is mandatory for commands registered as services
        parent::__construct();
    }

    /**
     * @return string[]
     */
    public function extractViewTypes(string $contentTypeIdentifier): array
    {
        $validViews = [];

        /** @var array<string, mixed[]> $contentView */
        $contentView = $this->configResolver->getParameter('ng_content_view');

        foreach ($contentView as $view => $viewConfigList) {
            if ($view === ViewManagerInterface::VIEW_TYPE_FULL) {
                continue;
            }

            foreach ($viewConfigList as $viewConfig) {
                $contentTypeMatch = (array) ($viewConfig['match']['Identifier\ContentType'] ?? []);

                if (in_array($contentTypeIdentifier, $contentTypeMatch, true)) {
                    $validViews[] = $view;
                }
            }
        }

        $validViews = array_values(array_unique($validViews));
        sort($validViews);

        return $validViews;
    }

    protected function configure(): void
    {
        $this->setDescription('Generates layout that acts as a showcase of all content & components available in Ibexa');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->style = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string[] $componentContentTypes */
        $componentContentTypes = array_keys($this->configResolver->getParameter('ibexa_component.parent_locations', 'netgen_layouts'));
        $showcaseLocation = $this->namedObjectProvider->getLocation('showcase');

        $query = new Query();
        $query->filter = new Criterion\LogicalAnd(
            [
                new Criterion\ParentLocationId($showcaseLocation->id),
                new Criterion\ContentTypeIdentifier($componentContentTypes),
            ],
        );

        $searchResult = $this->repository->sudo(fn (): SearchResult => $this->filterService->filterContent($query));
        $componentContentItems = $this->extractContentItems($searchResult);

        $query = new Query();
        $query->filter = new Criterion\LogicalAnd(
            [
                new Criterion\ParentLocationId($showcaseLocation->id),
                new Criterion\LogicalNot(
                    new Criterion\ContentTypeIdentifier($componentContentTypes),
                ),
            ],
        );

        $searchResult = $this->repository->sudo(fn (): SearchResult => $this->filterService->filterContent($query));
        $contentItems = $this->extractContentItems($searchResult);

        $blockConfigs = $this->configResolver->getParameter('showcase.blocks', 'ngsite');

        $this->transactionService->transaction(
            function () use ($showcaseLocation, $blockConfigs, $contentItems, $componentContentItems): void {
                $this->removeShowcaseLayoutAndMapping($showcaseLocation);

                $layoutCreateStruct = $this->layoutService->newLayoutCreateStruct(
                    $this->layoutTypeRegistry->getLayoutType('layout_1'),
                    sprintf('Showcase for "%s" siteaccess', $this->siteAccess->name),
                    $this->localeConverter->convertToPOSIX($this->configResolver->getParameter('languages')[0]) ?? 'en',
                );

                $layoutDraft = $this->layoutService->createLayout($layoutCreateStruct);

                foreach ($componentContentItems as $componentContent) {
                    $componentDefinition = $this->findComponentDefinition($componentContent);

                    if ($componentDefinition === null) {
                        continue;
                    }

                    foreach ($this->extractViewTypes($componentContent->contentInfo->contentTypeIdentifier) as $viewType) {
                        $this->createComponentTitle($layoutDraft, $componentContent, $viewType);
                        $this->createComponentBlock($layoutDraft, $componentDefinition, $componentContent, $viewType);
                    }
                }

                foreach ($blockConfigs as $blockConfig) {
                    $parameterCombinations = $this->createParameterCombinations(
                        $this->blockDefinitionRegistry->getBlockDefinition($blockConfig['block_definition']),
                        $blockConfig['variable_parameters'],
                    );

                    foreach ($blockConfig['item_view_types'] as $itemViewType) {
                        foreach ($parameterCombinations as $parameterCombination) {
                            $this->createBlockTitle(
                                $layoutDraft,
                                $blockConfig,
                                $itemViewType,
                                $parameterCombination,
                            );

                            $this->createConfiguredBlock(
                                $layoutDraft,
                                $blockConfig,
                                $itemViewType,
                                $contentItems,
                                $parameterCombination,
                            );
                        }
                    }
                }

                $layout = $this->layoutService->publishLayout($layoutDraft);
                $this->createMappingRule($layout, $showcaseLocation);

                $this->style->success(sprintf('Generated new showcase layout and mapping for "%s" siteaccess.', $this->siteAccess->name));
            },
        );

        return Command::SUCCESS;
    }

    private function removeShowcaseLayoutAndMapping(Location $showcaseLocation): void
    {
        $query = $this->connection->createQueryBuilder();

        $query->select('DISTINCT r.uuid')
            ->from('nglayouts_rule', 'r')
            ->innerJoin('r', 'nglayouts_rule_target', 't', $query->expr()->eq('r.id', 't.rule_id'))
            ->innerJoin('r', 'nglayouts_rule_condition_rule', 'cr', $query->expr()->eq('r.id', 'cr.rule_id'))
            ->innerJoin('cr', 'nglayouts_rule_condition', 'c', $query->expr()->eq('cr.condition_id', 'c.id'))
            ->where(
                $query->expr()->and(
                    $query->expr()->eq('r.status', ':status'),
                    $query->expr()->or(
                        $query->expr()->and(
                            $query->expr()->eq('t.type', ':locationType'),
                            $query->expr()->eq('t.value', ':locationValue'),
                        ),
                        $query->expr()->and(
                            $query->expr()->eq('t.type', ':contentType'),
                            $query->expr()->eq('t.value', ':contentValue'),
                        ),
                    ),
                    $query->expr()->and(
                        $query->expr()->eq('c.type', ':conditionType'),
                        sprintf('JSON_CONTAINS(c.value, \'["%s"]\')', $this->siteAccess->name),
                    ),
                ),
            )->setParameter('status', Value::STATUS_PUBLISHED)
            ->setParameter('locationType', 'ibexa_location', Types::STRING)
            ->setParameter('locationValue', $showcaseLocation->id, Types::STRING)
            ->setParameter('contentType', 'ibexa_content', Types::STRING)
            ->setParameter('contentValue', $showcaseLocation->contentId, Types::STRING)
            ->setParameter('conditionType', 'ibexa_site_access', Types::STRING);

        $ruleUuids = array_column($query->execute()->fetchAllAssociative(), 'uuid');

        foreach ($ruleUuids as $ruleUuid) {
            $rule = $this->layoutResolverService->loadRule(Uuid::fromString($ruleUuid));

            if ($rule->getLayout() instanceof Layout) {
                $this->style->info(sprintf('Removing obsolete showcase layout named "%s".', $rule->getLayout()->getName()));

                $this->layoutService->deleteLayout($rule->getLayout());
            }

            $this->style->info(sprintf('Removing obsolete mapping with "%s" UUID.', $rule->getId()->toString()));

            $this->layoutResolverService->deleteRule($rule);
        }
    }

    /**
     * @return string[]
     */
    private function extractViewContentTypes(string $viewName): array
    {
        $contentViewConfig = $this->configResolver->getParameter('ng_content_view');

        $contentTypes = [];

        foreach ($contentViewConfig[$viewName] as $viewConfig) {
            $contentTypes = [
                ...$contentTypes,
                ...($viewConfig['match']['Identifier\ContentType'] ?? []),
            ];
        }

        return array_values(array_unique($contentTypes));
    }

    private function findComponentDefinition(Content $content): ?BlockDefinitionInterface
    {
        foreach ($this->blockTypeRegistry->getBlockTypes(true) as $blockType) {
            if (!$blockType->getDefinition()->getHandler() instanceof ComponentHandler) {
                continue;
            }

            if ($blockType->getDefaultParameters()['content_type_identifier'] === $content->contentInfo->contentTypeIdentifier) {
                return $blockType->getDefinition();
            }
        }

        return null;
    }

    private function createComponentTitle(
        Layout $layoutDraft,
        Content $content,
        string $viewType,
    ): void {
        $titleCreateStruct = $this->blockService->newBlockCreateStruct(
            $this->blockDefinitionRegistry->getBlockDefinition('title'),
        );

        $title = sprintf('%s (%s)', $content->contentInfo->contentTypeName, $viewType);

        $titleCreateStruct->viewType = 'title';
        $titleCreateStruct->setParameterValue('tag', 'h2');
        $titleCreateStruct->setParameterValue('vertical_whitespace:enabled', true);
        $titleCreateStruct->setParameterValue('vertical_whitespace:top', 'large');
        $titleCreateStruct->setParameterValue('vertical_whitespace:bottom', 'large');
        $titleCreateStruct->setParameterValue('set_container', true);
        $titleCreateStruct->setParameterValue('title', $title);

        $this->blockService->createBlockInZone(
            $titleCreateStruct,
            $layoutDraft->getZone('main'),
        );
    }

    private function createComponentBlock(
        Layout $layoutDraft,
        BlockDefinitionInterface $definition,
        Content $content,
        string $viewType,
    ): void {
        $blockCreateStruct = $this->blockService->newBlockCreateStruct($definition);
        $blockCreateStruct->viewType = $viewType;
        $blockCreateStruct->itemViewType = 'standard';

        $blockCreateStruct->setParameterValue('content', $content->id);
        $blockCreateStruct->setParameterValue('content_type_identifier', $content->contentInfo->contentTypeIdentifier);
        $blockCreateStruct->setParameterValue('set_container', true);

        $this->blockService->createBlockInZone(
            $blockCreateStruct,
            $layoutDraft->getZone('main'),
        );
    }

    /**
     * @param array<string, mixed> $blockConfig
     * @param array<string, mixed> $parameters
     */
    private function createBlockTitle(
        Layout $layoutDraft,
        array $blockConfig,
        string $itemViewType,
        array $parameters,
    ): void {
        $titleCreateStruct = $this->blockService->newBlockCreateStruct(
            $this->blockDefinitionRegistry->getBlockDefinition('title'),
        );

        $parametersString = '';

        if ($parameters !== []) {
            $titleParts = array_map(
                static fn (string $name, mixed $value): string => sprintf('%s: %s', $name, var_export($value, true)),
                array_keys($parameters),
                array_values($parameters),
            );

            $parametersString = sprintf('(%s)', implode(', ', $titleParts));
        }

        $title = str_replace(
            ['%block%', '%viewType%', '%itemViewType%', '%parameters%'],
            [$blockConfig['block_definition'], $blockConfig['view_type'], $itemViewType, $parametersString],
            $blockConfig['title'] ?? '%viewType% block (%itemViewType% view) %parameters%',
        );

        $titleCreateStruct->viewType = 'title';
        $titleCreateStruct->setParameterValue('tag', 'h2');
        $titleCreateStruct->setParameterValue('vertical_whitespace:enabled', true);
        $titleCreateStruct->setParameterValue('vertical_whitespace:top', 'large');
        $titleCreateStruct->setParameterValue('vertical_whitespace:bottom', 'large');
        $titleCreateStruct->setParameterValue('set_container', true);
        $titleCreateStruct->setParameterValue('title', $title);

        $this->blockService->createBlockInZone(
            $titleCreateStruct,
            $layoutDraft->getZone('main'),
        );
    }

    /**
     * @param array<string, mixed> $blockConfig
     * @param \Netgen\IbexaSiteApi\API\Values\Content[] $contentItems
     * @param array<string, mixed> $parameters
     */
    private function createConfiguredBlock(
        Layout $layoutDraft,
        array $blockConfig,
        string $itemViewType,
        array $contentItems,
        array $parameters,
    ): void {
        $blockCreateStruct = $this->blockService->newBlockCreateStruct(
            $this->blockDefinitionRegistry->getBlockDefinition($blockConfig['block_definition']),
        );

        $blockCreateStruct->viewType = $blockConfig['view_type'];
        $blockCreateStruct->itemViewType = $itemViewType;

        foreach (($parameters + $blockConfig['parameters']) as $parameterName => $parameterValue) {
            $blockCreateStruct->setParameterValue($parameterName, $parameterValue);
        }

        $collectionCreateStruct = $this->collectionService->newCollectionCreateStruct();
        $blockCreateStruct->addCollectionCreateStruct('default', $collectionCreateStruct);

        $block = $this->blockService->createBlockInZone(
            $blockCreateStruct,
            $layoutDraft->getZone('main'),
        );

        $this->createCollection($block, $blockConfig, $itemViewType, $contentItems);
    }

    /**
     * @param array<string, mixed> $blockConfig
     * @param \Netgen\IbexaSiteApi\API\Values\Content[] $contentItems
     */
    private function createCollection(Block $block, array $blockConfig, string $itemViewType, array $contentItems): void
    {
        $collection = $block->getCollection('default');

        foreach ($contentItems as $content) {
            $validContentTypes = $blockConfig['included_content_types'];

            if ((bool) $blockConfig['use_view_content_types']) {
                $validContentTypes = [...$validContentTypes, ...$this->extractViewContentTypes($itemViewType)];
            }

            if (!in_array($content->contentInfo->contentTypeIdentifier, $validContentTypes, true)) {
                continue;
            }

            if (in_array($content->contentInfo->contentTypeIdentifier, $blockConfig['excluded_content_types'], true)) {
                continue;
            }

            $itemCreateStruct = $content->mainLocation instanceof Location ?
                $this->collectionService->newItemCreateStruct(
                    $this->itemDefinitionRegistry->getItemDefinition('ibexa_location'),
                    $content->mainLocation->id,
                ) :
                $this->collectionService->newItemCreateStruct(
                    $this->itemDefinitionRegistry->getItemDefinition('ibexa_content'),
                    $content->id,
                );

            $this->collectionService->addItem($collection, $itemCreateStruct);
        }
    }

    /**
     * @param string[] $parameters
     *
     * @return array<int, array<string, mixed>>
     */
    private function createParameterCombinations(BlockDefinitionInterface $blockDefinition, array $parameters): array
    {
        $validParameterValues = [];

        foreach ($parameters as $parameterName) {
            $parameterDefinition = $this->findParameterDefinition($blockDefinition, $parameterName);
            if ($parameterDefinition === null) {
                continue;
            }

            $validValues = match ($parameterDefinition->getType()::getIdentifier()) {
                BooleanType::getIdentifier(), CompoundBooleanType::getIdentifier() => [true, false],
                ChoiceType::getIdentifier() => array_values($parameterDefinition->getOption('options')),
                default => null,
            };

            if ($validValues === null) {
                continue;
            }

            $validParameterValues[$parameterName] = $validValues;
        }

        $combinations = [[]]; // Start with one empty combination

        // Go through each parameter and its list of valid values
        foreach ($validParameterValues as $parameterName => $parameterValues) {
            $newCombinations = [];

            // Add each valid value to each existing combination
            foreach ($combinations as $combination) {
                foreach ($parameterValues as $parameterValue) {
                    $newCombination = $combination;
                    $newCombination[$parameterName] = $parameterValue;
                    $newCombinations[] = $newCombination;
                }
            }

            // Update the list of combinations
            $combinations = $newCombinations;
        }

        return $combinations;
    }

    private function findParameterDefinition(
        ParameterDefinitionCollectionInterface $parameterDefinitionCollection,
        string $parameterName,
    ): ?ParameterDefinition {
        if ($parameterDefinitionCollection->hasParameterDefinition($parameterName)) {
            return $parameterDefinitionCollection->getParameterDefinition($parameterName);
        }

        foreach ($parameterDefinitionCollection->getParameterDefinitions() as $innerParameterDefinition) {
            if (!$innerParameterDefinition instanceof ParameterDefinitionCollectionInterface) {
                continue;
            }

            $parameterDefinition = $this->findParameterDefinition($innerParameterDefinition, $parameterName);

            if ($parameterDefinition !== null) {
                return $parameterDefinition;
            }
        }

        return null;
    }

    private function createMappingRule(Layout $layout, Location $location): void
    {
        $priority = (int) $this->configResolver->getParameter('showcase.rule_priority', 'ngsite');
        $ruleGroupUuid = (string) $this->configResolver->getParameter('showcase.rule_group_uuid', 'ngsite');

        $ruleCreateStruct = $this->layoutResolverService->newRuleCreateStruct();
        $ruleCreateStruct->layoutId = $layout->getId();
        $ruleCreateStruct->priority = $priority;

        $ruleDraft = $this->layoutResolverService->createRule(
            $ruleCreateStruct,
            $this->layoutResolverService->loadRuleGroup(Uuid::fromString($ruleGroupUuid)),
        );

        $targetCreateStruct = $this->layoutResolverService->newTargetCreateStruct('ibexa_location');
        $targetCreateStruct->value = $location->id;

        $this->layoutResolverService->addTarget($ruleDraft, $targetCreateStruct);

        $conditionCreateStruct = $this->layoutResolverService->newConditionCreateStruct('ibexa_site_access');
        $conditionCreateStruct->value = [$this->siteAccess->name];

        $this->layoutResolverService->addRuleCondition($ruleDraft, $conditionCreateStruct);

        $this->layoutResolverService->publishRule($ruleDraft);
    }
}
