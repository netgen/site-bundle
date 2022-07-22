<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\QueryType;

use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\QueryType\OptionsResolverBasedQueryType;
use Netgen\Bundle\SiteBundle\API\Search\Criterion\FullText;
use Netgen\EzPlatformSiteApi\API\Site;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function trim;

class SearchQueryType extends OptionsResolverBasedQueryType
{
    protected Site $site;

    protected ConfigResolverInterface $configResolver;

    public function __construct(Site $site, ConfigResolverInterface $configResolver)
    {
        $this->site = $site;
        $this->configResolver = $configResolver;
    }

    public static function getName(): string
    {
        return 'NetgenSite:Search';
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['search_text', 'content_types', 'subtree']);

        $resolver->setAllowedTypes('search_text', 'string');
        $resolver->setAllowedTypes('content_types', 'string[]');
        $resolver->setAllowedTypes('subtree', ['int', 'string']);

        $resolver->setAllowedValues(
            'search_text',
            static function (string $searchText): bool {
                if (empty(trim($searchText))) {
                    return false;
                }

                return true;
            },
        );

        $resolver->setDefault('content_types', $this->configResolver->getParameter('search.content_types', 'ngsite'));
        $resolver->setDefault('subtree', $this->site->getSettings()->rootLocationId);
    }

    protected function doGetQuery(array $parameters): Query
    {
        $subtreeLocation = $this->site->getLoadService()->loadLocation($parameters['subtree']);

        $criteria = [
            new Criterion\Subtree($subtreeLocation->pathString),
            new Criterion\Visibility(Criterion\Visibility::VISIBLE),
        ];

        if (!empty($parameters['content_types'])) {
            $criteria[] = new Criterion\ContentTypeIdentifier($parameters['content_types']);
        }

        $query = new LocationQuery();
        $query->query = new FullText(trim($parameters['search_text']));
        $query->filter = new Criterion\LogicalAnd($criteria);

        return $query;
    }
}
