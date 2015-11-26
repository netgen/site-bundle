<?php

namespace Netgen\Bundle\MoreBundle\Core\Repository;

use eZ\Publish\Core\Repository\Repository as BaseRepository;
use eZ\Publish\API\Repository\Repository as RepositoryInterface;
use eZ\Publish\API\Repository\Values\User\User;
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

    /**
     * Check if user has access to a given module / function
     *
     * Low level function, use canUser instead if you have objects to check against.
     *
     * Overriden because original sudo flag is set to private so original hasAccess method
     * does not see our sudo flag. WE HATE PRIVATE!!
     *
     * @param string $module
     * @param string $function
     * @param \eZ\Publish\API\Repository\Values\User\User $user
     *
     * @return boolean|array Bool if user has full or no access, array if limitations if not
     */
    public function hasAccess( $module, $function, User $user = null )
    {
        // Full access if sudoFlag is set by {@see sudo()}
        if ( $this->sudoFlag === true )
        {
            return true;
        }

        return parent::hasAccess( $module, $function, $user );
    }
}
