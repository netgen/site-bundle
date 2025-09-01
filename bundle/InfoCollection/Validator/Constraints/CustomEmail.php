<?php

namespace Netgen\Bundle\SiteBundle\InfoCollection\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class CustomEmail extends Constraint
{
    public string $message = 'email_is_not_valid';
}