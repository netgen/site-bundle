<?php

namespace Netgen\Bundle\MoreBundle\Assetic\Filter;

use Assetic\Filter\UglifyJs2Filter as BaseUglifyJs2Filter;
use Assetic\Asset\AssetInterface;
use Assetic\Exception\FilterException;

class UglifyJs2Filter extends BaseUglifyJs2Filter
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
