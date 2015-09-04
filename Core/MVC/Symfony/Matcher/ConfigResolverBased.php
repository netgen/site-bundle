<?php

namespace Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Matcher;

use eZ\Publish\Core\MVC\ConfigResolverInterface;

abstract class ConfigResolverBased extends MultipleValued
{
    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $configResolver;

    /**
     * Constructor
     *
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     */
    public function __construct( ConfigResolverInterface $configResolver )
    {
        $this->configResolver = $configResolver;
    }

    public function doMatch( $value )
    {
        $config = $this->values[0];
        $namespace = isset( $this->values[1] ) ? $this->values[1] : null;

        if ( $this->configResolver->hasParameter( $config, $namespace ) )
        {
            $configValue = $this->configResolver->getParameter( $config, $namespace );
            $configValue = !is_array( $configValue ) ? array( $configValue ) : $configValue;

            return in_array( $value, $configValue );
        }

        return false;
    }
}
