<?php

namespace Netgen\Bundle\MoreBundle\View;

use eZ\Bundle\EzPublishCoreBundle\View\Manager as BaseManager;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\Core\MVC\Symfony\View\ViewManagerInterface;
use eZ\Publish\Core\MVC\Symfony\View\ContentViewInterface;
use RuntimeException;

class Manager extends BaseManager
{
    /**
     * Renders $content by selecting the right template.
     * $content will be injected in the selected template.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Content $content
     * @param string $viewType Variation of display for your content. Default is 'full'.
     * @param array $parameters Parameters to pass to the template called to
     *        render the view. By default, it's empty. 'content' entry is
     *        reserved for the Content that is rendered.
     *
     * @throws \RuntimeException
     *
     * @return string
     */
    public function renderContent( Content $content, $viewType = ViewManagerInterface::VIEW_TYPE_FULL, $parameters = array() )
    {
        $contentInfo = $content->getVersionInfo()->getContentInfo();
        foreach ( $this->getAllContentViewProviders() as $viewProvider )
        {
            $view = $viewProvider->getView( $contentInfo, $viewType, $parameters );
            if ( $view instanceof ContentViewInterface )
            {
                $parameters['content'] = $content;

                return $this->renderContentView( $view, $parameters );
            }
        }

        throw new RuntimeException( "Unable to find a template for #$contentInfo->id" );
    }
}
