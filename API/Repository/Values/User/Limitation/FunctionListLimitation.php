<?php

namespace Netgen\Bundle\MoreBundle\API\Repository\Values\User\Limitation;

use eZ\Publish\API\Repository\Values\User\Limitation;

class FunctionListLimitation extends Limitation
{
    const FUNCTIONLIST = "FunctionList";

    /**
     * @see \eZ\Publish\API\Repository\Values\User\Limitation::getIdentifier()
     *
     * @return string
     */
    public function getIdentifier()
    {
        return self::FUNCTIONLIST;
    }
}
