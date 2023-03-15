<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\API\Search\Criterion;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\FullText as BaseFullText;
use Netgen\IbexaSearchExtra\API\Values\Content\Query\Criterion\FulltextSpellcheck;
use Netgen\IbexaSearchExtra\API\Values\Content\SpellcheckQuery;
use RuntimeException;

use function get_debug_type;
use function is_string;
use function sprintf;

final class FullText extends BaseFullText implements FulltextSpellcheck
{
    /**
     * Gets query to be used for spell check.
     */
    public function getSpellcheckQuery(): SpellcheckQuery
    {
        if (!is_string($this->value)) {
            throw new RuntimeException(
                sprintf('FullText criterion value should be a string, %s given', get_debug_type($this->value)),
            );
        }

        $spellcheckQuery = new SpellcheckQuery();
        $spellcheckQuery->query = $this->value;
        $spellcheckQuery->count = 10;

        return $spellcheckQuery;
    }
}
