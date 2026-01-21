<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\InfoCollection\Validator\Constraints;

use Ibexa\Contracts\ContentForms\Data\Content\FieldData;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use function array_filter;
use function property_exists;

final class RequiredFieldValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        /** @var FieldData $value */
        if (
            $value->value === null
            || array_filter(
                $value->value->attributes(),
                static fn ($attr) => $value->value->attribute($attr) === null || $value->value->attribute($attr) === '' || $value->value->attribute($attr) === false,
            ) !== []
        ) {
            if (!property_exists($constraint, 'message')) {
                return;
            }
            $this->context->buildViolation($constraint->message)
                ->atPath($value->fieldDefinition->identifier)
                ->addViolation();
        }
    }
}
