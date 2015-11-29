<?php

namespace BartdeZwaan\EventSourcing\Async\EventHandling;

use Broadway\Domain\DomainMessage;

interface MessageHandler
{
    /**
     * Listen to incoming messages.
     *
     * @param AsyncEventBus $eventBus
     */
    public function listen(AsyncEventBus $eventBus);

    /**
     * @param DomainMessage $domainMessage
     */
    public function publish(DomainMessage $domainMessage);
}

