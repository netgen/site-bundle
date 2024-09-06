<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Templating\Twig;

use Composer\InstalledVersions;
use Twig\Environment as BaseEnvironment;
use Twig\Source;

use function sprintf;
use function str_replace;
use function version_compare;

final class Environment extends BaseEnvironment
{
    public function compileSource(Source $source): string
    {
        $compiledSource = parent::compileSource($source);

        if (!$this->isDebug()) {
            return $compiledSource;
        }

        $className = DebugTemplate::class;
        if (version_compare(InstalledVersions::getVersion('twig/twig') ?? '', '3.12.0', '<')) {
            $className = LegacyDebugTemplate::class;
        }

        return str_replace(
            ' extends Template',
            sprintf(' extends %s', $className),
            $compiledSource,
        );
    }
}
