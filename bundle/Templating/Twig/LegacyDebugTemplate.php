<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Templating\Twig;

use Twig\Source;

class LegacyDebugTemplate extends BaseDebugTemplate
{
    /**
     * @return string
     */
    public function getTemplateName()
    {
        return '';
    }

    /**
     * @return \Twig\Source
     */
    public function getSourceContext()
    {
        return new Source('', '');
    }

    /**
     * @return array<int, int>
     */
    public function getDebugInfo()
    {
        return [];
    }
}
