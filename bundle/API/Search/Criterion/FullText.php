<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\API\Search\Criterion;

use eZ\Publish\API\Repository\Values\Content\Query\Criterion\FullText as BaseFullText;
use Netgen\EzPlatformSearchExtra\API\Values\Content\Query\Criterion\FulltextSpellcheck;
use Netgen\EzPlatformSearchExtra\API\Values\Content\SpellcheckQuery;

class FullText extends BaseFullText implements FulltextSpellcheck
{
    /**
     * Gets query to be used for spell check.
     */
    public function getSpellcheckQuery(): SpellcheckQuery
    {
        $spellcheckQuery = new SpellcheckQuery();
        $spellcheckQuery->query = $this->value;
        $spellcheckQuery->count = 10;

        return $spellcheckQuery;
    }
}
