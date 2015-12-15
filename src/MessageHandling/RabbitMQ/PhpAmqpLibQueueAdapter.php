<?php

namespace Zwaan\EventSourcing\MessageHandling\RabbitMQ;

use Broadway\Domain\DomainMessage;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class PhpAmqpLibQueueAdapter implements Adapter
{
    protected $connection;
    protected $channel;
    protected $queueName;

    /**
     * @param string               $queueName
     * @param AMQPStreamConnection $connection
     */
    public function __construct($queueName, AMQPStreamConnection $connection)
    {
        $this->connection   = $connection;
        $this->queueName    = $queueName;
        $this->initializeQueue();
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

        $this->channel->basic_publish($msg, '', $this->queueName);
    }

    private function initializeQueue()
    {
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($this->queueName, false, false, false, false);
    }
}

