<?php

namespace Netgen\Bundle\MoreBundle\EventListener;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Stash\Interfaces\DriverInterface;
use Stash\Pool;

class DisableInMemoryStashDriverEventListener implements EventSubscriberInterface
{
    /**
     * @var \Stash\Pool
     */
    protected $cachePool;

    /**
     * @var \Stash\Interfaces\DriverInterface
     */
    protected $scriptDriver;

    /**
     * Constructor.
     *
     * @param \Stash\Pool $cachePool
     * @param \Stash\Interfaces\DriverInterface $scriptDriver
     */
    public function __construct(Pool $cachePool, DriverInterface $scriptDriver = null)
    {
        $this->cachePool = $cachePool;
        $this->scriptDriver = $scriptDriver;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            ConsoleEvents::COMMAND => 'onConsoleCommand',
        );
    }

    /**
     * Disables the in memory Stash driver for console commands.
     *
     * @param \Symfony\Component\Console\Event\ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        if (!$this->scriptDriver instanceof DriverInterface) {
            return;
        }

        if (in_array(mb_strtolower(getenv('STASH_DISABLE_INMEMORY')), array('1', 'true'))) {
            $this->cachePool->setDriver($this->scriptDriver);
        }
    }
}
