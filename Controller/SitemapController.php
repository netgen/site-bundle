<?php

namespace Netgen\Bundle\MoreBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use Symfony\Component\HttpFoundation\Response;

class SitemapController extends Controller
{
    /**
     * Fetches sitemap items and renders them.
     *
     * @param string $template
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewSitemap($template = null)
    {
        $sitemapItems = array();

        $response = new Response();

        $response->setPublic()->setSharedMaxAge(86400);
        $response->setContent(
            $this->render(
                $template !== null ? $template : 'NetgenMoreBundle::sitemap.html.twig',
                array(
                    'items' => $sitemapItems,
                )
            )
        );

        return $response;
    }
}
