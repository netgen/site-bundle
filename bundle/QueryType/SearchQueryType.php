<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\QueryType;

use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\QueryType\OptionsResolverBasedQueryType;
use Netgen\Bundle\SiteBundle\API\Search\Criterion\FullText;
use Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion\Visible;
use Netgen\IbexaSiteApi\API\Site;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function class_exists;
use function count;
use function in_array;
use function is_a;
use function trim;

final class SearchQueryType extends OptionsResolverBasedQueryType
{
    public function __construct(
        private readonly Site $site,
        private readonly ConfigResolverInterface $configResolver,
    ) {}

    public static function getName(): string
    {
        return 'NetgenSite:Search';
    }

    protected function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setRequired(['search_text', 'content_types', 'subtree', 'sort', 'order']);

        $optionsResolver->setAllowedTypes('search_text', 'string');
        $optionsResolver->setAllowedTypes('content_types', 'string[]');
        $optionsResolver->setAllowedTypes('sort', 'string[]');
        $optionsResolver->setAllowedTypes('subtree', ['int', 'string']);

        $optionsResolver->setAllowedValues(
            'search_text',
            static fn (string $searchText): bool => trim($searchText) !== '',
        );

        $optionsResolver->setAllowedValues(
            'sort',
            fn (array $classNames): bool => $this->validateSortConfig($classNames),
        );

        $optionsResolver->setAllowedValues(
            'order',
            static fn (string $order): bool => in_array($order, [Query::SORT_ASC, Query::SORT_DESC], true),
        );

        $optionsResolver->setDefault('content_types', $this->configResolver->getParameter('search.content_types', 'ngsite'));
        $optionsResolver->setDefault('subtree', $this->site->getSettings()->rootLocationId);
        $optionsResolver->setDefault('sort', [SortClause\DatePublished::class]);
        $optionsResolver->setDefault('order', Query::SORT_DESC);
    }

    protected function doGetQuery(array $parameters): Query
    {
        $subtreeLocation = $this->site->getLoadService()->loadLocation($parameters['subtree']);

        $criteria = [
            new Criterion\Subtree($subtreeLocation->pathString),
            new Visible(true),
        ];

        if (count($parameters['content_types']) > 0) {
            $criteria[] = new Criterion\ContentTypeIdentifier($parameters['content_types']);
        }

        $query = new LocationQuery();
        $query->query = new FullText(trim($parameters['search_text']));
        $query->filter = new Criterion\LogicalAnd($criteria);

        $sortClauses = [];

        /** @var class-string<\Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause> $className */
        foreach ($parameters['sort'] as $className) {
            $sortClauses[] = new $className($parameters['order']);
        }

        $query->sortClauses = $sortClauses;

        return $query;
    }

    /**
     * @param string[] $classNames
     */
    private function validateSortConfig(array $classNames): bool
    {
        foreach ($classNames as $className) {
            if (!class_exists($className) || !is_a($className, SortClause::class, true)) {
                return false;
            }
        }

        return true;
    }
}
