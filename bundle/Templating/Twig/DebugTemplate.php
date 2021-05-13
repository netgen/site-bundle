<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\Templating\Twig;

use Symfony\Component\Filesystem\Filesystem;
use Twig\Source;
use Twig\Template;
use function dirname;
use function getcwd;
use function mb_stripos;
use function mb_strlen;
use function mb_substr;
use function ob_get_clean;
use function ob_start;
use function preg_replace;
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

    public function display(array $context, array $blocks = [])
    {
        $this->fileSystem = $this->fileSystem ?: new Filesystem();

        // Bufferize to be able to insert template name as HTML comments if applicable.
        // Layout template name will only appear at the end, to avoid potential quirks with old browsers
        // when comments appear before doctype declaration.
        ob_start();
        parent::display($context, $blocks);
        $templateResult = ob_get_clean();

        $templateName = trim($this->fileSystem->makePathRelative($this->getSourceContext()->getPath(), dirname(getcwd())), '/');
        // Check if template name ends with "html.twig", indicating this is an HTML template.
        $isHtmlTemplate = mb_substr($templateName, -mb_strlen('html.twig')) === 'html.twig';
        $templateName = $isHtmlTemplate ? $templateName . ' (' . $this->getSourceContext()->getName() . ')' : $templateName;

        // Display start template comment, if applicable.
        if ($isHtmlTemplate) {
            if (mb_stripos(trim($templateResult), '<!doctype') !== false) {
                $templateResult = preg_replace(
                    '#(<!doctype[^>]+>)#im',
                    "$1\n<!-- START " . $templateName . ' -->',
                    $templateResult,
                );
            } else {
                echo "\n<!-- START {$templateName} -->\n";
            }
        }

        // Display stop template comment after result, if applicable.
        if ($isHtmlTemplate) {
            $bodyPos = mb_stripos($templateResult, '</body>');
            if ($bodyPos !== false) {
                // Add layout template name before </body>, to avoid display quirks in some browsers.
                echo mb_substr($templateResult, 0, $bodyPos)
                     . "\n<!-- STOP {$templateName} -->\n"
                     . mb_substr($templateResult, $bodyPos);
            } else {
                echo $templateResult;
                echo "\n<!-- STOP {$templateName} -->\n";
            }
        } else {
            echo $templateResult;
        }
    }

    public function getTemplateName()
    {
        return '';
    }

    public function getSourceContext()
    {
        return new Source('', '');
    }

    public function getDebugInfo()
    {
        return [];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        return '';
    }
}
