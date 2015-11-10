<?php

namespace Netgen\Bundle\MoreBundle\Core\Repository;

use eZ\Publish\Core\Repository\Repository as BaseRepository;
use eZ\Publish\API\Repository\Repository as RepositoryInterface;
use RuntimeException;
use Exception;
use Closure;

class Repository extends BaseRepository
{
    /**
     * Flag to specify if current execution is sudo mode, only set by {@see sudo()}.
     *
     * @var bool
     */
    private $sudoFlag = false;

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
     * @param \eZ\Publish\API\Repository\Repository $outerRepository
     *
     * @throws \RuntimeException Thrown on recursive sudo() use.
     * @throws \Exception Re throws exceptions thrown inside $callback
     * @return mixed
     */
    public function sudo( Closure $callback, RepositoryInterface $outerRepository = null )
    {
        if ( $this->sudoFlag === true )
        {
            throw new RuntimeException( "Recursive sudo use detected, abort abort!" );
        }

        $this->sudoFlag = true;
        try
        {
            $returnValue = $callback( $outerRepository !== null ? $outerRepository : $this );
        }
        catch ( Exception $e  )
        {
            $this->sudoFlag = false;
            throw $e;
        }

        $this->sudoFlag = false;
        return $returnValue;
    }
}
