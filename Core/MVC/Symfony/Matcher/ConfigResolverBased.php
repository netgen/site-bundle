<?php

declare(strict_types=1);

namespace Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Matcher;

use eZ\Publish\Core\MVC\ConfigResolverInterface;

abstract class ConfigResolverBased extends MultipleValued
{
    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

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
     *
     * @return bool
     */
    public function doMatch($value): bool
    {
        $config = $this->values[0];
        $namespace = $this->values[1] ?? null;

        if ($this->configResolver->hasParameter($config, $namespace)) {
            $configValue = $this->configResolver->getParameter($config, $namespace);
            $configValue = !is_array($configValue) ? array($configValue) : $configValue;

            return in_array($value, $configValue, true);
        }

        return false;
    }
}
