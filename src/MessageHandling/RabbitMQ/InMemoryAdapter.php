<?php

namespace BartdeZwaan\EventSourcing\Async\MessageHandling\RabbitMQ;

use Broadway\Domain\DomainMessage;
use PhpAmqpLib\Message\AMQPMessage;

class InMemoryAdapter implements Adapter
{
    /**
     * @var array<AMQPMessage>
     */
    private $queue = [];

    /**
     * {@inheritDoc}
     */
    public function listen($callback)
    {
        $consumerCallback = function($msg) use ($callback) {
            call_user_func($callback, $msg);
        };

        foreach ($this->queue as $message) {
            call_user_func(
                $consumerCallback,
                $message
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function publish($msg)
    {
        $msg = new AMQPMessage($msg);

        $this->queue[] = $msg;
    }
}

