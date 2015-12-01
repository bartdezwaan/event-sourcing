<?php

namespace BartdeZwaan\EventSourcing\Async\EventHandling;

use BartdeZwaan\EventSourcing\Async\Serializer\Serializer;
use Broadway\Domain\DomainMessage;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMQMessageHandler implements MessageHandler
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
     * @param Serializer           $serializer
     */
    public function __construct($exchangeName, $queueName, AMQPStreamConnection $connection, $serializer)
    {
        $this->connection   = $connection;
        $this->exchangeName = $exchangeName;
        $this->queueName    = $queueName;
        $this->serializer   = $serializer;
        $this->initializeExchange();
    }

    /**
     * {@inheritDoc}
     */
    public function listen(AsyncEventBus $eventBus)
    {
        $this->channel->queue_bind($this->queueName, $this->exchangeName);

        $callback = function($msg) use ($eventBus){
            try {
                $eventBus->handle($this->serializer->deserialize(json_decode($msg->body, true)));
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            } catch (\Exception $e) {
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            };
        };

        $this->channel->basic_consume($this->queueName, '', false, false, false, false, $callback);

        while(count($this->channel->callbacks)) {
            $this->channel->wait();
        }

        $this->channel->close();
        $this->connection->close();
    }

    /**
     * {@inheritDoc}
     */
    public function publish(DomainMessage $domainMessage)
    {
        $msg = new AMQPMessage(json_encode($this->serializer->serialize($domainMessage)));

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

