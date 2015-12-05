<?php

namespace BartdeZwaan\EventSourcing\Async\EventHandling;

use BartdeZwaan\EventSourcing\Async\MessageHandling\RabbitMQ\Adapter;
use Broadway\Domain\DomainMessage;
use Broadway\Serializer\SerializerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMQMessageHandler implements MessageHandler
{
    public function __construct(Adapter $adapter, SerializerInterface $serializer)
    {
        $this->adapter    = $adapter;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritDoc}
     */
    public function listen(AsyncEventBus $eventBus)
    {
        $callback = function($msg) use ($eventBus){
            $eventBus->handle($this->serializer->deserialize(json_decode($msg->body, true)));
        };

        $this->adapter->listen($callback);
    }

    /**
     * {@inheritDoc}
     */
    public function publish(DomainMessage $domainMessage)
    {
        $msg = json_encode($this->serializer->serialize($domainMessage));
        $this->adapter->publish($msg);
    }

    private function initializeExchange()
    {
        $this->channel = $this->connection->channel();
        $this->channel->exchange_declare($this->exchangeName, 'fanout', false, false, false);
        $this->channel->queue_declare($this->queueName, false, false, false, false);
    }
}

