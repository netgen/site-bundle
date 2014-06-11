<?php

namespace Netgen\Bundle\MoreBundle\Core\Limitation;

use eZ\Publish\Core\Limitation\AbstractPersistenceLimitationType;
use eZ\Publish\API\Repository\Values\ValueObject;
use eZ\Publish\API\Repository\Values\User\User as APIUser;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\VersionInfo;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentType;
use Netgen\Bundle\MoreBundle\API\Repository\Values\User\Limitation\FunctionListLimitation as APIFunctionListLimitation;
use eZ\Publish\API\Repository\Values\User\Limitation as APILimitationValue;
use eZ\Publish\SPI\Limitation\Type as SPILimitationTypeInterface;
use eZ\Publish\API\Repository\Exceptions\NotImplementedException;

class FunctionListLimitationType extends AbstractPersistenceLimitationType implements SPILimitationTypeInterface
{
    /**
     * Accepts a Limitation value and checks for structural validity.
     *
     * Makes sure LimitationValue object and ->limitationValues is of correct type.
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException If the value does not match the expected type/structure
     *
     * @param \eZ\Publish\API\Repository\Values\User\Limitation $limitationValue
     */
    public function acceptValue( APILimitationValue $limitationValue )
    {
        if ( !$limitationValue instanceof APIFunctionListLimitation )
        {
            throw new InvalidArgumentType( "\$limitationValue", "APIFunctionListLimitation", $limitationValue );
        }
        else if ( !is_array( $limitationValue->limitationValues ) )
        {
            throw new InvalidArgumentType( "\$limitationValue->limitationValues", "array", $limitationValue->limitationValues );
        }

        foreach ( $limitationValue->limitationValues as $key => $value )
        {
            if ( !is_string( $value ) )
            {
                throw new InvalidArgumentType( "\$limitationValue->limitationValues[{$key}]", "int", $value );
            }
        }
    }

    /**
     * Makes sure LimitationValue->limitationValues is valid according to valueSchema().
     *
     * Make sure {@link acceptValue()} is checked first!
     *
     * @param \eZ\Publish\API\Repository\Values\User\Limitation $limitationValue
     *
     * @return \eZ\Publish\SPI\FieldType\ValidationError[]
     */
    public function validate( APILimitationValue $limitationValue )
    {
        $validationErrors = array();

        // @TODO: Check against available eZ JS Core function lists in legacy kernel in ezjscore.ini/ezjscServer/FunctionList

        return $validationErrors;
    }

    /**
     * Create the Limitation Value
     *
     * The is the method to create values as Limitation type needs value knowledge anyway in acceptValue,
     * the reverse relation is provided by means of identifier lookup (Value has identifier, and so does RoleService).
     *
     * @param mixed[] $limitationValues
     *
     * @return \eZ\Publish\API\Repository\Values\User\Limitation
     */
    public function buildValue( array $limitationValues )
    {
        return new APIFunctionListLimitation( array( 'limitationValues' => $limitationValues ) );
    }

    /**
     * Evaluate permission against content and placement
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException If any of the arguments are invalid
     *         Example: If LimitationValue is instance of ContentTypeLimitationValue, and Type is SectionLimitationType.
     * @throws \eZ\Publish\API\Repository\Exceptions\BadStateException If value of the LimitationValue is unsupported
     *         Example if OwnerLimitationValue->limitationValues[0] is not one of: [ 1,  2 ]
     *
     * @param \eZ\Publish\API\Repository\Values\User\Limitation $value
     * @param \eZ\Publish\API\Repository\Values\User\User $currentUser
     * @param \eZ\Publish\API\Repository\Values\ValueObject $object
     * @param \eZ\Publish\API\Repository\Values\ValueObject[]|null $targets An array of location, parent or "assignment"
     *                                                                 objects, if null: none where provided by caller
     *
     * @return boolean
     */
    public function evaluate( APILimitationValue $value, APIUser $currentUser, ValueObject $object, array $targets = null )
    {
        if ( !$value instanceof APIFunctionListLimitation )
        {
            throw new InvalidArgumentException( '$value', 'Must be of type: APIFunctionListLimitation' );
        }

        if ( !$object instanceof ContentInfo && !$object instanceof Content && !$object instanceof VersionInfo )
        {
            throw new InvalidArgumentException( '$object', 'Must be of type: Content, VersionInfo or ContentInfo' );
        }

        return false;
    }

    /**
     * Returns Criterion for use in find() query
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotImplementedException If the limitation does not support
     *         being used as a Criterion.
     *
     * @param \eZ\Publish\API\Repository\Values\User\Limitation $value
     * @param \eZ\Publish\API\Repository\Values\User\User $currentUser
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Query\CriterionInterface
     */
    public function getCriterion( APILimitationValue $value, APIUser $currentUser )
    {
        throw new NotImplementedException( __METHOD__ );
    }

    /**
     * Returns info on valid $limitationValues

     * @throws \eZ\Publish\API\Repository\Exceptions\NotImplementedException If the limitation does not support
     *         value schema.
     *
     * @return mixed[]|int In case of array, a hash with key as valid limitations value and value as human readable name
     *                     of that option, in case of int on of VALUE_SCHEMA_* constants.
     *                     Note: The hash might be an instance of Traversable, and not a native php array.
     */
    public function valueSchema()
    {
        throw new NotImplementedException( __METHOD__ );
    }
}
