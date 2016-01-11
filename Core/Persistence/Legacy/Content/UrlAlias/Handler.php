<?php

namespace Netgen\Bundle\MoreBundle\Core\Persistence\Legacy\Content\UrlAlias;

use eZ\Publish\Core\Persistence\Legacy\Content\UrlAlias\Handler as BaseHandler;

class Handler extends BaseHandler
{
    /**
     * @param string $text
     *
     * @return string
     */
    protected function getHash($text)
    {
        return md5(mb_strtolower($text, 'UTF-8'));
    }
}
