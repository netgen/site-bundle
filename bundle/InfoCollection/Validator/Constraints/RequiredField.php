<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\InfoCollection\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class RequiredField extends Constraint
{
    public string $message = 'this_value_should_not_be_blank';
}
