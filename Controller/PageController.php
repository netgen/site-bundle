<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller\PageController as BasePageController;
use eZ\Publish\Core\FieldType\Page\Parts\Block;

class PageController extends BasePageController
{
    /**
     * Render the block.
     *
     * @param \eZ\Publish\Core\FieldType\Page\Parts\Block $block
     * @param array $params
     * @param array $cacheSettings settings for the HTTP cache, 'smax-age' and
     *        'max-age' are checked.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewBlock(Block $block, array $params = array(), array $cacheSettings = array())
    {
        $configResolver = $this->getConfigResolver();

        if (!isset($cacheSettings['smax-age'])) {
            if ($configResolver->hasParameter('BlockSettings.' . $block->type . '.SharedMaxAge', 'ngmore')) {
                $cacheSettings['smax-age'] = (int)$configResolver->getParameter('BlockSettings.' . $block->type . '.SharedMaxAge', 'ngmore');
            }
        }

        if (!isset($cacheSettings['max-age'])) {
            if ($configResolver->hasParameter('BlockSettings.' . $block->type . '.MaxAge', 'ngmore')) {
                $cacheSettings['max-age'] = (int)$configResolver->getParameter('BlockSettings.' . $block->type . '.MaxAge', 'ngmore');
            }
        }

        return parent::viewBlock($block, $params, $cacheSettings);
    }
}
