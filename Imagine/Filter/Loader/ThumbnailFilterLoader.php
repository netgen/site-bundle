<?php

namespace Netgen\Bundle\MoreBundle\Imagine\Filter\Loader;

use eZ\Bundle\EzPublishCoreBundle\Imagine\Filter\Loader\FilterLoaderWrapped;
use Imagine\Image\ImageInterface;
use Imagine\Exception\InvalidArgumentException;

class ThumbnailFilterLoader extends FilterLoaderWrapped
{
    public function load( ImageInterface $image, array $options = array() )
    {
        if ( count( $options ) < 3 )
        {
            throw new InvalidArgumentException( 'Invalid options for ngmore/thumbnail filter. You must provide array(width, height, mode)' );
        }

        return $this->innerLoader->load(
            $image,
            array(
                'size' => array( $options[0], $options[1] ),
                'mode' => $options[2]
            )
        );
    }
}
