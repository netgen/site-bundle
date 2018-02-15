<?php

namespace Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Matcher;

abstract class MultipleValued
{
    /**
     * @var array Values to test against
     */
    protected $values;

    /**
     * Registers the matching configuration for the matcher.
     * $matchingConfig can have single (string|int...) or multiple values (array).
     *
     * @param mixed $matchingConfig
     *
     * @throws \InvalidArgumentException Should be thrown if $matchingConfig is not valid
     */
    public function setMatchingConfig($matchingConfig)
    {
        $this->values = !is_array($matchingConfig) ? array($matchingConfig) : $matchingConfig;
    }
}
