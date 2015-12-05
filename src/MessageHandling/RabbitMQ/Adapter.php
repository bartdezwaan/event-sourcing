<?php

namespace Zwaan\EventSourcing\MessageHandling\RabbitMQ;

interface Adapter
{
    /**
     * @param callable $callback
     */
    public function listen($callback);

    /**
     * @param mixed $msg
     */
    public function publish($msg);
}

