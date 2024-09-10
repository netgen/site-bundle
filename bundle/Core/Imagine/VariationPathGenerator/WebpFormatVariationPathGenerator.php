<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Core\Imagine\VariationPathGenerator;

use eZ\Bundle\EzPublishCoreBundle\Imagine\VariationPathGenerator;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;

use function pathinfo;
use function preg_replace;

/**
 * Decorates VariationPathGenerator with .webp extension if image variation is configured for this format.
 */
final class WebpFormatVariationPathGenerator implements VariationPathGenerator
{
    private VariationPathGenerator $innerVariationPathGenerator;

    private FilterConfiguration $filterConfiguration;

    public function __construct(
        VariationPathGenerator $innerVariationPathGenerator,
        FilterConfiguration $filterConfiguration
    ) {
        $this->innerVariationPathGenerator = $innerVariationPathGenerator;
        $this->filterConfiguration = $filterConfiguration;
    }

    public function getVariationPath($originalPath, $filter): string
    {
        $variationPath = $this->innerVariationPathGenerator->getVariationPath($originalPath, $filter);
        $filterConfig = $this->filterConfiguration->get($filter);

        if (!isset($filterConfig['format']) || $filterConfig['format'] !== 'webp') {
            return $variationPath;
        }

        $info = pathinfo($originalPath);

        if (!is_string($info['extension']) || strlen($info['extension']) === 0) {
            return $variationPath . '.webp';
        }

        return preg_replace("/\.{$info['extension']}$/", '.webp', $variationPath);
    }
}
