<?php

namespace Zwaan\EventSourcing\MessageHandling\RabbitMQ;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class PhpAmqpLibFQueueAdapterFactory
{
    private $connection;

    /**
     * @param AMQPStreamConnection $connection
     */
    public function __construct(AMQPStreamConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $queueName
     *
     * @return PhpAmqpLibQueueAdapter
     */
    public function create($queueName)
    {
        return new PhpAmqpLibQueueAdapter($queueName, $this->connection);
    }
}

