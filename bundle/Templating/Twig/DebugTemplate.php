<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Templating\Twig;

use Twig\Source;

class DebugTemplate extends BaseDebugTemplate
{
    public function getTemplateName(): string
    {
        return '';
    }

    public function getSourceContext(): Source
    {
        return new Source('', '');
    }

    public function getDebugInfo(): array
    {
        return [];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        return [];
    }
}
