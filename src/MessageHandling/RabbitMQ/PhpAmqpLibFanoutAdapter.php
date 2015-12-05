<?php

namespace Zwaan\EventSourcing\MessageHandling\RabbitMQ;

use Broadway\Domain\DomainMessage;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class PhpAmqpLibFanoutAdapter implements Adapter
{
    protected $connection;
    protected $channel;
    protected $exchangeName;
    protected $queueName;
    protected $serializer;

    /**
     * @param string               $exchangeName
     * @param string               $queueName
     * @param AMQPStreamConnection $connection
     */
    public function __construct($exchangeName, $queueName, AMQPStreamConnection $connection)
    {
        $this->connection   = $connection;
        $this->exchangeName = $exchangeName;
        $this->queueName    = $queueName;
        $this->initializeExchange();
    }

    /**
     * {@inheritDoc}
     */
    public function listen($callback)
    {
        $consumerCallback = function($msg) use ($callback) {
            try {
                call_user_func($callback, $msg);
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            } catch (\Exception $e) {
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            };
        };

        $this->channel->basic_consume($this->queueName, '', false, false, false, false, $consumerCallback);

        while(count($this->channel->callbacks)) {
            $this->channel->wait();
        }

        $this->channel->close();
        $this->connection->close();
    }

    /**
     * {@inheritDoc}
     */
    public function publish($msg)
    {
        $msg = new AMQPMessage($msg);

        $this->channel->queue_bind($this->queueName, $this->exchangeName);
        $this->channel->basic_publish($msg, $this->exchangeName);

        $this->channel->close();
        $this->connection->close();
    }

    private function initializeExchange()
    {
        $this->channel = $this->connection->channel();
        $this->channel->exchange_declare($this->exchangeName, 'fanout', false, false, false);
        $this->channel->queue_declare($this->queueName, false, false, false, false);
    }
}

