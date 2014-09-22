<?php

namespace Netgen\Bundle\MoreBundle\API\Repository\Values\Content\Query\SortClause;

use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause\Target\FieldTarget;

class FieldLength extends SortClause
{
    /**
     * Constructs a new FieldLength SortClause on Type $typeIdentifier and Field $fieldIdentifier
     *
     * @param string $typeIdentifier
     * @param string $fieldIdentifier
     * @param string $sortDirection
     * @param null|string $languageCode
     */
    public function __construct( $typeIdentifier, $fieldIdentifier, $sortDirection = Query::SORT_ASC, $languageCode = null )
    {
        parent::__construct(
            'field',
            $sortDirection,
            new FieldTarget( $typeIdentifier, $fieldIdentifier, $languageCode )
        );
    }
}
