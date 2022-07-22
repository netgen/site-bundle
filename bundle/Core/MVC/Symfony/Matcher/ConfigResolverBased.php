<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Core\MVC\Symfony\Matcher;

use eZ\Publish\Core\MVC\ConfigResolverInterface;

use function in_array;
use function is_array;

abstract class ConfigResolverBased extends MultipleValued
{
    protected ConfigResolverInterface $configResolver;

    public function __construct(ConfigResolverInterface $configResolver)
    {
        $this->configResolver = $configResolver;
    }

    /**
     * Performs the match against the provided value.
     *
     * This works by comparing the value against the parameter from config resolver.
     *
     * First element in the value array should be the name of the parameter and the
     * second should be the namespace.
     *
     * @param mixed $value
     */
    public function doMatch($value): bool
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
