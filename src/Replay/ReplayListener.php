<?php

namespace Zwaan\EventSourcing\Replay;

interface ReplayListener
{
    /**
     * Listen to events from a sender.
     *
     * @param mixed $sender
     */
    public function listen($sender);
}
