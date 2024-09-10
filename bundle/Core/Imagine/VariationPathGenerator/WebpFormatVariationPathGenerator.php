<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Core\Imagine\VariationPathGenerator;

use Ibexa\Contracts\Core\Variation\VariationPathGenerator;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;

use function array_key_exists;
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
        FilterConfiguration $filterConfiguration,
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

        if (!array_key_exists('extension', $info) || $info['extension'] === '') {
            return $variationPath . '.webp';
        }

        return preg_replace("/\\.{$info['extension']}$/", '.webp', $variationPath) ?? $variationPath;
    }
}
