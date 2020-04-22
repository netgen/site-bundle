<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Templating\Twig;

use Twig\Environment as BaseEnvironment;
use Twig\Source;

class Environment extends BaseEnvironment
{
    public function compileSource(Source $source): string
    {
        $compiledSource = parent::compileSource($source);

        if (!$this->isDebug()) {
            return $compiledSource;
        }

        return str_replace(
            ' extends Template',
            sprintf(' extends %s', DebugTemplate::class),
            $compiledSource
        );
    }
}
