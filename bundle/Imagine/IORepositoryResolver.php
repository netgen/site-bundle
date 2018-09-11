<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Imagine;

use eZ\Bundle\EzPublishCoreBundle\Imagine\IORepositoryResolver as BaseIORepositoryResolver;

class IORepositoryResolver extends BaseIORepositoryResolver
{
    /**
     * Returns empty string to disable absolute image URLs.
     *
     * Temporary solution until https://github.com/ezsystems/ezpublish-kernel/pull/1137 is merged.
     */
    protected function getBaseUrl(): string
    {
        return '';
    }
}
