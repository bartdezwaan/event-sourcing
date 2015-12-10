<?php

namespace Zwaan\EventSourcing\Replay;

interface ReplayRequestHandler
{
    /**
     * @param Generator $events
     * @param mixed     $receiver
     */
    public function replay($events, $receiver);
}

