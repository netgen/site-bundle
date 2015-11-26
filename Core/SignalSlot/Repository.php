<?php

namespace Netgen\Bundle\MoreBundle\Core\SignalSlot;

use eZ\Publish\Core\SignalSlot\Repository as BaseRepository;
use Closure;

class Repository extends BaseRepository
{
    /**
     * Allows API execution to be performed with full access sand-boxed
     *
     * The closure sandbox will do a catch all on exceptions and rethrow after
     * re-setting the sudo flag.
     *
     * Example use:
     *     $location = $repository->sudo(
     *         function ( $repo ) use ( $locationId )
     *         {
     *             return $repo->getLocationService()->loadLocation( $locationId )
     *         }
     *     );
     *
     * @access private This function is not official API atm, and can change anytime.
     *
     * @param \Closure $callback
     *
     * @throws \RuntimeException Thrown on recursive sudo() use.
     * @throws \Exception Re throws exceptions thrown inside $callback
     * @return mixed
     */
    public function sudo( Closure $callback )
    {
        return $this->repository->sudo( $callback, $this );
    }
}
