<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\QueryType;

use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\QueryType\OptionsResolverBasedQueryType;
use Netgen\Bundle\SiteBundle\API\Search\Criterion\FullText;
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
            /** @var string[] $keys */
            fn (array $keys): bool => $this->sortKeysAllowed($keys),
        );
        $optionsResolver->setAllowedValues(
            'order',
            static fn (string $order): bool => in_array($order, [Query::SORT_ASC, Query::SORT_DESC], true),
        );
        $optionsResolver->setDefault('content_types', $this->configResolver->getParameter('search.content_types', 'ngsite'));
        $optionsResolver->setDefault('subtree', $this->site->getSettings()->rootLocationId);
        $optionsResolver->setDefault('sort', [Query\SortClause\DatePublished::class]);
        $optionsResolver->setDefault('order', Query::SORT_DESC);
    }

    protected function doGetQuery(array $parameters): Query
    {
        $subtreeLocation = $this->site->getLoadService()->loadLocation($parameters['subtree']);
        $sortingKeys = $parameters['sort'];
        $criteria = [
            new Criterion\Subtree($subtreeLocation->pathString),
            new Criterion\Visibility(Criterion\Visibility::VISIBLE),
        ];
        if (count($parameters['content_types']) > 0) {
            $criteria[] = new Criterion\ContentTypeIdentifier($parameters['content_types']);
        }
        $query = new LocationQuery();
        $query->query = new FullText(trim($parameters['search_text']));
        $query->filter = new Criterion\LogicalAnd($criteria);
        $sortClauses = [];
        foreach ($sortingKeys as $sortingKey) {
            $sortClauses[] = new $sortingKey($parameters['order']);
        }
        $query->sortClauses = $sortClauses;

        return $query;
    }

    /**
     * @param string[] $keys
     *
     * @return bool
     */
    private function sortKeysAllowed(array $keys): bool
    {
        foreach ($keys as $key) {
            if (!class_exists($key) || !is_a($key, SortClause::class, true)) {
                return false;
            }
        }

        return true;
    }
}
