<?php

namespace Netgen\Bundle\MoreBundle\Core\MVC\Symfony\Controller;

use eZ\Publish\Core\MVC\Symfony\Controller\Manager as BaseManager;
use eZ\Publish\Core\FieldType\Page\Parts\Block;
use eZ\Publish\API\Repository\Values\ValueObject;
use eZ\Publish\Core\MVC\Symfony\Matcher\ContentBasedMatcherFactory;
use eZ\Publish\Core\MVC\Symfony\Matcher\BlockMatcherFactory;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Psr\Log\LoggerInterface;

class Manager extends BaseManager
{
    /**
     * @var \eZ\Publish\Core\MVC\Symfony\Matcher\BlockMatcherFactory
     */
    protected $blockMatcherFactory;

    public function __construct(
        ContentBasedMatcherFactory $locationMatcherFactory,
        ContentBasedMatcherFactory $contentMatcherFactory,
        BlockMatcherFactory $blockMatcherFactory,
        LoggerInterface $logger
    )
    {
        parent::__construct( $locationMatcherFactory, $contentMatcherFactory, $logger );
        $this->blockMatcherFactory = $blockMatcherFactory;
    }

    /**
     * Returns a ControllerReference object corresponding to $valueObject and $viewType
     *
     * @param \eZ\Publish\API\Repository\Values\ValueObject $valueObject
     * @param string $viewType
     *
     * @throws \InvalidArgumentException
     *
     * @return \Symfony\Component\HttpKernel\Controller\ControllerReference|null
     */
    public function getControllerReference( ValueObject $valueObject, $viewType )
    {
        if ( $valueObject instanceof Block )
        {
            $configHash = $this->blockMatcherFactory->match( $valueObject, $viewType );
            if ( !is_array( $configHash ) || !isset( $configHash['controller'] ) )
            {
                return;
            }

            $this->logger->debug( "Matched custom controller '{$configHash['controller']}' for Block #$valueObject->id" );
            return new ControllerReference( $configHash['controller'] );
        }

        return parent::getControllerReference( $valueObject, $viewType );
    }
}
