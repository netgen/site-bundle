<?php

namespace Netgen\Bundle\MoreBundle\Assetic\Filter;

use Assetic\Filter\UglifyCssFilter as BaseUglifyCssFilter;
use Assetic\Asset\AssetInterface;
use Assetic\Exception\FilterException;

class UglifyCssFilter extends BaseUglifyCssFilter
{
    public function filterDump(AssetInterface $asset)
    {
        try {
            parent::filterDump($asset);
        } catch (FilterException $e) {
            $e->setInput(null);
            throw $e;
        }
    }
}
