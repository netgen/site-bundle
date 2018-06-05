<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\QueryType;

use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\QueryType\OptionsResolverBasedQueryType;
use eZ\Publish\Core\QueryType\QueryType;
use Netgen\EzPlatformSiteApi\API\Site;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchQueryType extends OptionsResolverBasedQueryType implements QueryType
{
    /**
     * @var \Netgen\EzPlatformSiteApi\API\Site
     */
    protected $site;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    public function __construct(Site $site, ConfigResolverInterface $configResolver)
    {
        $this->site = $site;
        $this->configResolver = $configResolver;
    }

    public static function getName(): string
    {
        return 'NetgenMore:Search';
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['search_text', 'content_types', 'subtree']);

        $resolver->setAllowedTypes('search_text', 'string');
        $resolver->setAllowedTypes('content_types', 'string[]');
        $resolver->setAllowedTypes('subtree', ['int', 'string']);

        $resolver->setAllowedValues(
            'search_text',
            function (string $searchText) {
                if (empty(trim($searchText))) {
                    return false;
                }

                return true;
            }
        );

        $resolver->setDefault('content_types', $this->configResolver->getParameter('search.content_types', 'ngmore'));
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
        $query->query = new Criterion\FullText(trim($parameters['search_text']));
        $query->filter = new Criterion\LogicalAnd($criteria);

        return $query;
    }
}
