<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Templating\Twig;

use Twig\Source;

class LegacyDebugTemplate extends BaseDebugTemplate
{
    public function getTemplateName(): string
    {
        return '';
    }

    public function getSourceContext(): Source
    {
        return new Source('', '');
    }

    /**
     * @return array<int, int>
     */
    public function getDebugInfo(): array
    {
        return [];
    }

    protected function doDisplay(array $context, array $blocks = []): void {}
}
