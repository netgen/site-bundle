<?php
/**
 * File containing a DoctrineDatabase sort clause handler class
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version 2014.07.0
 */

namespace Netgen\Bundle\MoreBundle\Core\Persistence\Legacy\Content\Search\Common\Gateway\SortClauseHandler;

use eZ\Publish\Core\Persistence\Database\SelectQuery;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\Core\Persistence\Legacy\Content\Search\Common\Gateway\SortClauseHandler\Field;
use Netgen\Bundle\MoreBundle\API\Repository\Values\Content\Query\SortClause\FieldLength as APIFieldLength;

class FieldLength extends Field
{
    /**
     * Check if this sort clause handler accepts to handle the given sort clause.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query\SortClause $sortClause
     *
     * @return boolean
     */
    public function accept( SortClause $sortClause )
    {
        return $sortClause instanceof APIFieldLength;
    }

    /**
     * Apply selects to the query
     *
     * Returns the name of the (aliased) column, which information should be
     * used for sorting.
     *
     * @param \eZ\Publish\Core\Persistence\Database\SelectQuery $query
     * @param \eZ\Publish\API\Repository\Values\Content\Query\SortClause $sortClause
     * @param int $number
     *
     * @return string
     */
    public function applySelect( SelectQuery $query, SortClause $sortClause, $number )
    {
        $columns = parent::applySelect( $query, $sortClause, $number );

        return array_map(
            function( $column )
            {
                return 'LENGTH( ' . $column . ' )';
            },
            $columns
        );
    }
}
