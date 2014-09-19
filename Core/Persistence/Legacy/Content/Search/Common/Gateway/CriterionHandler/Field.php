<?php

namespace Netgen\Bundle\MoreBundle\Core\Persistence\Legacy\Content\Search\Common\Gateway\CriterionHandler;

use eZ\Publish\Core\Persistence\Legacy\Content\Search\Common\Gateway\CriterionHandler\Field as BaseFieldCriterionHandler;
use Netgen\Bundle\MoreBundle\API\Repository\Values\Content\Query\Criterion\Field as APIField;
use eZ\Publish\Core\Persistence\Legacy\Content\FieldValue\Converter;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use RuntimeException;

class Field extends BaseFieldCriterionHandler
{
    /**
     * Check if this criterion handler accepts to handle the given criterion.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query\Criterion $criterion
     *
     * @return boolean
     */
    public function accept( Criterion $criterion )
    {
        return $criterion instanceof APIField;
    }

    /**
     * This method is specifically overriden to disable the check if the field is searchable or not
     *
     * Returns relevant field information for the specified field
     *
     * The returned information is returned as an array of the attribute
     * identifier and the sort column, which should be used.
     *
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentException If no fields are found for the given $fieldIdentifier.
     *
     * @caching
     * @param string $fieldIdentifier
     *
     * @return array
     */
    protected function getFieldsInformation( $fieldIdentifier )
    {
        $query = $this->dbHandler->createSelectQuery();
        $query
            ->select(
                $this->dbHandler->quoteColumn( 'id', 'ezcontentclass_attribute' ),
                $this->dbHandler->quoteColumn( 'data_type_string', 'ezcontentclass_attribute' )
            )
            ->from(
                $this->dbHandler->quoteTable( 'ezcontentclass_attribute' )
            )
            ->where(
                $query->expr->eq(
                    $this->dbHandler->quoteColumn( 'identifier', 'ezcontentclass_attribute' ),
                    $query->bindValue( $fieldIdentifier )
                )
            );

        $statement = $query->prepare();
        $statement->execute();
        if ( !( $rows = $statement->fetchAll( \PDO::FETCH_ASSOC ) ) )
        {
            throw new InvalidArgumentException(
                "\$criterion->target",
                "No fields found for the given criterion target '{$fieldIdentifier}'."
            );
        }

        $fieldMapArray = array();
        foreach ( $rows as $row )
        {
            if ( !isset( $fieldMapArray[ $row['data_type_string'] ] ) )
            {
                $converter = $this->fieldConverterRegistry->getConverter( $row['data_type_string'] );

                if ( !$converter instanceof Converter )
                {
                    throw new RuntimeException(
                        "getConverter({$row['data_type_string']}) did not return a converter, got: " .
                        gettype( $converter )
                    );
                }

                $fieldMapArray[ $row['data_type_string'] ] = array(
                    'ids' => array(),
                    'column' => $converter->getIndexColumn(),
                );
            }

            $fieldMapArray[ $row['data_type_string'] ]['ids'][] = $row['id'];
        }

        return $fieldMapArray;
    }
}
