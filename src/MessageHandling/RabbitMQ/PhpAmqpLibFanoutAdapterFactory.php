<?php

namespace Zwaan\EventSourcing\MessageHandling\RabbitMQ;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class PhpAmqpLibFanoutAdapterFactory
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
     * @param string $exchangeName
     * @param string $queueName
     *
     * @return PhpAmqpLibFanoutAdapter
     */
    public function create($exchangeName, $queueName)
    {
        return new PhpAmqpLibFanoutAdapter($exchangeName, $queueName, $this->connection);
    }
}

