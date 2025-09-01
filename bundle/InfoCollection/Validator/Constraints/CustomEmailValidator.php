<?php

namespace Netgen\Bundle\SiteBundle\InfoCollection\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use function filter_var;

final class CustomEmailValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof CustomEmail) {
            throw new UnexpectedTypeException($constraint, CustomEmail::class);
        }

        $emailValue = $value->value->email;

        if (filter_var($emailValue, FILTER_VALIDATE_EMAIL) === false) {
            $this->context->buildViolation($constraint->message)
                ->atPath($value->fieldDefinition->identifier)
                ->addViolation();
        }
    }
}