<?php

namespace Netgen\Bundle\MoreBundle\Templating\Converter;

use eZ\Publish\API\Repository\Values\User\User;
use eZ\Publish\Core\MVC\Legacy\Templating\Converter\ObjectConverter;
use InvalidArgumentException;
use Closure;
use eZUser;

class UserConverter implements ObjectConverter
{
    /**
     * @var \Closure
     */
    protected $legacyKernel;

    /**
     * Constructor.
     *
     * @param \Closure $legacyKernel
     */
    public function __construct(Closure $legacyKernel)
    {
        $this->legacyKernel = $legacyKernel;
    }

    /**
     * Converts $object to make it compatible with legacy eZTemplate API.
     *
     * @param \eZ\Publish\API\Repository\Values\User\User $object
     *
     * @throws \InvalidArgumentException If $object is actually not an object
     *
     * @return \eZUser
     */
    public function convert($object)
    {
        if (!$object instanceof User) {
            throw new InvalidArgumentException('$object is not a User instance');
        }

        $legacyKernel = $this->legacyKernel;

        return $legacyKernel()->runCallback(
            function () use ($object) {
                return eZUser::fetchByName($object->login);
            },
            false,
            false
        );
    }
}
