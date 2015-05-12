<?php

namespace Netgen\Bundle\MoreBundle\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler\FieldValue\Handler;

use eZ\Publish\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler\FieldValue\Handler\Composite as BaseComposite;

use Netgen\Bundle\MoreBundle\API\Repository\Values\Content\Query\Criterion\Field;
use eZ\Publish\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler\FieldValue\Handler;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\Core\Persistence\Database\SelectQuery;

class Composite extends BaseComposite
{
    /**
     * Generates query expression for operator and value of a Field Criterion.
     *
     * @throws \RuntimeException If operator is not handled.
     *
     * @param \eZ\Publish\Core\Persistence\Database\SelectQuery $query
     * @param \eZ\Publish\API\Repository\Values\Content\Query\Criterion $criterion
     * @param string $column
     *
     * @return \eZ\Publish\Core\Persistence\Database\Expression
     */
    public function handle( SelectQuery $query, Criterion $criterion, $column )
    {
        if ( $criterion->operator !== Field::REVERSE_LIKE )
        {
            return parent::handle( $query, $criterion, $column );
        }

        $column = " TRIM( " . $this->dbHandler->quoteColumn( $column ) . " ) ";

        $filter = $query->expr->lAnd(
            $query->expr->gt( $query->expr->length( $column ), 0 ),
            $query->expr->like(
                $query->bindValue( $this->lowercase( trim( $criterion->value ) ) ),
                $query->expr->concat( $query->expr->lower( $column ), "'%'" )
            )
        );

        return $filter;
    }
}
