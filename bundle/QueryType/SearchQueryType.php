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

use function array_key_exists;
use function count;
use function trim;

final class SearchQueryType extends OptionsResolverBasedQueryType
{
    /*
     * It is a mapper that contains a set of key-value pairs, where key is
     * allowed sort parameter value and value is name of class
     * that provides implementation of that key
     */
    /** @var array<string, string> */
    private array $sortKeysAllowed;

    public function __construct(
        private readonly Site $site,
        private readonly ConfigResolverInterface $configResolver,
    ) {
        $this->sortKeysAllowed = $this->configResolver->getParameter('search.sort_allowed_keys', 'ngsite');
    }

    public static function getName(): string
    {
        return 'NetgenSite:Search';
    }

    protected function doGetQuery(array $parameters): Query
    {
        $subtreeLocation = $this->site->getLoadService()->loadLocation($parameters['subtree']);
        $sortingKeys = $parameters['sort'];
        $order = $parameters['order'];
        $descendingOrder = $order === 'desc';
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
            $sortClauses[] = $this->createSortClause($sortingKey, $descendingOrder);
        }
        $query->sortClauses = $sortClauses;

        return $query;
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
            static fn (array $keys): bool => $this->sortKeysAllowed($keys),
        );
        $optionsResolver->setAllowedValues(
            'order',
            static fn (string $searchText): bool => trim($searchText) === 'asc' || trim($searchText) === 'desc',
        );
        $optionsResolver->setDefault('content_types', $this->configResolver->getParameter('search.content_types', 'ngsite'));
        $optionsResolver->setDefault('subtree', $this->site->getSettings()->rootLocationId);
        $optionsResolver->setDefault('sort', ['published_date']);
        $optionsResolver->setDefault('order', 'desc');
    }

    private function sortKeyAllowed(string $key): bool
    {
        return array_key_exists(trim($key), $this->sortKeysAllowed);
    }

    private function sortKeysAllowed(array $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->sortKeyAllowed($key)) {
                return false;
            }
        }

        return true;
    }

    private function createSortClause(string $name, bool $desc = true): mixed
    {
        $className = $this->sortKeysAllowed[$name];
        if ($desc) {
            return new $className(Query::SORT_DESC);
        }

        return new $className(Query::SORT_ASC);
    }
}
