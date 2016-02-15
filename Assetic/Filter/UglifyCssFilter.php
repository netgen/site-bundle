<?php

namespace Netgen\Bundle\MoreBundle\Assetic\Filter;

use Assetic\Filter\UglifyCssFilter as BaseUglifyCssFilter;
use Assetic\Asset\AssetInterface;
use Assetic\Exception\FilterException;

class UglifyCssFilter extends BaseUglifyCssFilter
{
    /**
     * Filters an asset just before it's dumped.
     *
     * Overriden to disable setting the input to error output.
     *
     * @param \Assetic\Asset\AssetInterface $asset An asset
     */
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
