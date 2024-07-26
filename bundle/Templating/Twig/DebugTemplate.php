<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Templating\Twig;

use Symfony\Component\Filesystem\Filesystem;
use Twig\Source;
use Twig\Template;

use function dirname;
use function getcwd;
use function mb_stripos;
use function mb_substr;
use function preg_replace;
use function str_contains;
use function str_ends_with;
use function trim;

/**
 * Meant to be used as a Twig base template class.
 *
 * Wraps the display method to:
 * - Inject debug info into template to be able to see in the markup which one is used
 */
class DebugTemplate extends Template
{
    private Filesystem $fileSystem;

    public function render(array $context): string
    {
        $this->fileSystem = $this->fileSystem ?? new Filesystem();

        // Get the template result to be able to insert template name as HTML comments if applicable.
        // Layout template name will only appear at the end, to avoid potential quirks with old browsers
        // when comments appear before doctype declaration.
        $templateResult = parent::render($context);

        $templateName = trim($this->fileSystem->makePathRelative($this->getSourceContext()->getPath(), dirname((string) getcwd())), '/');
        $isHtmlTemplate = str_ends_with($templateName, 'html.twig');
        $templateName = $isHtmlTemplate ? $templateName . ' (' . $this->getSourceContext()->getName() . ')' : $templateName;

        if (!$isHtmlTemplate) {
            return $templateResult;
        }

        // Display start template comment
        if (str_contains(trim($templateResult), '<!doctype')) {
            $templateResult = (string) preg_replace(
                '#(<!doctype[^>]+>)#im',
                "$1\n<!-- START " . $templateName . ' -->',
                $templateResult,
            );
        } else {
            $templateResult = "\n<!-- START " . $templateName . " -->\n" . $templateResult;
        }

        // Display stop template comment after result, if applicable.
        if (str_contains($templateResult, '</body>')) {
            $bodyPos = (int) mb_stripos($templateResult, '</body>');
            // Add layout template name before </body>, to avoid display quirks in some browsers.
            $templateResult = mb_substr($templateResult, 0, $bodyPos)
                 . "\n<!-- STOP " . $templateName . " -->\n"
                 . mb_substr($templateResult, $bodyPos);
        } else {
            $templateResult .= "\n<!-- STOP " . $templateName . " -->\n";
        }

        return $templateResult;
    }

    public function getTemplateName()
    {
        return '';
    }

    public function getSourceContext()
    {
        return new Source('', '');
    }

    /**
     * @return array<string, mixed>
     */
    public function getDebugInfo()
    {
        return [];
    }

    protected function doDisplay(array $context, array $blocks = []) {}
}
