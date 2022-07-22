<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Core\Search;

use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use Netgen\EzPlatformSearchExtra\API\Values\Content\Search\Suggestion;

use function sprintf;
use function str_replace;

class SuggestionResolver
{
    /**
     * Get suggested search term based on returned spell check suggestions.
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
            throw new NotFoundException('suggestion', sprintf('for search term "%s"', $searchTerm));
        }

        return $suggestedTerm;
    }
}
