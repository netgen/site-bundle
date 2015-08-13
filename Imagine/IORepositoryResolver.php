<?php

namespace Netgen\Bundle\MoreBundle\Imagine;

use eZ\Bundle\EzPublishCoreBundle\Imagine\IORepositoryResolver as BaseIORepositoryResolver;

class IORepositoryResolver extends BaseIORepositoryResolver
{
    /**
     * Returns empty string to disable absolute image URLs.
     *
     * Temporary solution until https://github.com/ezsystems/ezpublish-kernel/pull/1137 is merged.
     *
     * @return string
     */
    protected function getBaseUrl()
    {
        return "";
    }
}
