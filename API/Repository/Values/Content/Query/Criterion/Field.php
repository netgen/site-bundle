<?php

namespace Netgen\Bundle\MoreBundle\API\Repository\Values\Content\Query\Criterion;

use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Field as BaseField;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator\Specifications;

class Field extends BaseField
{
    /**
     * Constant designating the reverse like match: criterionValue LIKE CONCAT( fieldValue, '%' )
     *
     * @const string
     */
    const REVERSE_LIKE = "reverse_like";

    public function getSpecifications()
    {
        $specifications = parent::getSpecifications();
        $specifications[] = new Specifications( self::REVERSE_LIKE, Specifications::FORMAT_SINGLE );

        return $specifications;
    }
}
