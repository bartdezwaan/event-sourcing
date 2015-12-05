<?php

namespace Zwaan\EventSourcing\EventHandling;

use Broadway\Domain\DomainMessage;

interface EventHandler
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

