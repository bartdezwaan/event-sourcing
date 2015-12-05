<?php

namespace Zwaan\EventSourcing\EventHandling;

use Broadway\Domain\DomainEventStreamInterface;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventBusInterface;
use Broadway\EventHandling\EventListenerInterface;
use Exception;

/**
 * Asynchronous publishing and consuming of events with RabbitMQ.
 */
class AsyncEventBus implements EventBusInterface
{
    private $eventListeners = array();
    private $queue          = array();
    private $isPublishing   = false;
    private $eventHandler;

    /**
     * @param EventHandler $eventHandler
     */
    public function __construct(EventHandler $eventHandler)
    {
        $this->eventHandler = $eventHandler;
    }

    /**
     * Listen to incoming messages.
     */
    public function listen()
    {
        $this->eventHandler->listen($this);
    }

    /**
     * {@inheritDoc}
     */
    public function subscribe(EventListenerInterface $eventListener)
    {
        $this->eventListeners[] = $eventListener;
    }

    /**
     * {@inheritDoc}
     */
    public function publish(DomainEventStreamInterface $domainMessages)
    {
        foreach ($domainMessages as $domainMessage) {
            $this->queue[] = $domainMessage;
        }

        if (! $this->isPublishing) {
            $this->isPublishing = true;

            try {
                while ($domainMessage = array_shift($this->queue)) {
                    $this->eventHandler->publish($domainMessage);
                }

                $this->isPublishing = false;
            } catch (Exception $e) {
                $this->isPublishing = false;
                throw $e;
            }
        }
    }

    /**
     * Handle domain message
     */
    public function handle(DomainMessage $domainMessage)
    {
        foreach ($this->eventListeners as $eventListener) {
            $eventListener->handle($domainMessage);
        }
    }
}

