<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Core\MVC\Symfony\Matcher;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;

use function in_array;
use function is_array;

abstract class ConfigResolverBased extends MultipleValued
{
    public function __construct(private ConfigResolverInterface $configResolver) {}

    /**
     * Performs the match against the provided value.
     *
     * This works by comparing the value against the parameter from config resolver.
     *
     * First element in the value array should be the name of the parameter and the
     * second should be the namespace.
     */
    public function doMatch(mixed $value): bool
    {
        $config = $this->values[0];
        $namespace = $this->values[1] ?? null;

        if ($this->configResolver->hasParameter($config, $namespace)) {
            $configValue = $this->configResolver->getParameter($config, $namespace);
            $configValue = !is_array($configValue) ? [$configValue] : $configValue;

            return in_array($value, $configValue, true);
        }

        return false;
    }
}
