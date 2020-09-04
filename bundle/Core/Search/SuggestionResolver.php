<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Core\Search;

use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use Netgen\EzPlatformSearchExtra\API\Values\Content\Search\Suggestion;

class SuggestionResolver
{
    /**
     * Get suggested search term based on returned spell check suggestions.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query $query
     * @param \Netgen\EzPlatformSearchExtra\API\Values\Content\Search\Suggestion $suggestion
     *
     * @return string
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    public function getSuggestedSearchTerm(Query $query, Suggestion $suggestion): string
    {
        $searchTerm = $query->query->value;
        $search = $suggestion->getOriginalWords();
        $replace = [];

        foreach ($suggestion->getOriginalWords() as $originalWord) {
            $replace[] = $suggestion->getSuggestionsByOriginalWord($originalWord)[0]->suggestedWord;
        }

        $suggestedTerm = str_replace($search, $replace, $searchTerm);

        if ($searchTerm === $suggestedTerm) {
            throw new NotFoundException('suggestion', 'for search term {'.$searchTerm.'}');
        }

        return $suggestedTerm;
    }
}
