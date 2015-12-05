<?php

namespace BartdeZwaan\EventSourcing\Async\EventHandling;

use BartdeZwaan\EventSourcing\Async\Serializer\Serializer;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBusInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMQEventListener
{
    protected $connection;
    protected $channel;
    protected $exchangeName;
    protected $queueName;
    protected $serializer;
    protected $eventBus;

    /**
     * @param string               $exchangeName
     * @param string               $queueName
     * @param AMQPStreamConnection $connection
     * @param Serializer           $serializer
     * @param EventBusInterface    $eventBus
     */
    public function __construct($exchangeName, $queueName, AMQPStreamConnection $connection, Serializer $serializer, EventBusInterface $eventBus)
    {
        $this->connection   = $connection;
        $this->exchangeName = $exchangeName;
        $this->queueName    = $queueName;
        $this->serializer   = $serializer;
        $this->eventBus     = $eventBus;
        $this->initializeExchange();
    }

    public function listen()
    {
        $this->channel->queue_bind($this->queueName, $this->exchangeName);

        $eventBus = $this->eventBus;
        $callback = function($msg) use ($eventBus){
            $this->eventBus->publish($this->getDomainEventStream($msg->body));
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };

        $this->channel->basic_consume($this->queueName, '', false, false, false, false, $callback);

        while(count($this->channel->callbacks)) {
            $this->channel->wait();
        }

        $this->channel->close();
        $this->connection->close();
    }

    protected function getDomainEventStream($event)
    {
        $domainEvent = $this->deserializeDomainMessage($event);
        $domainEventStream = new DomainEventStream([$domainEvent]);

        return $domainEventStream;
    }

    private function deserializeDomainMessage($event)
    {
        return $this->serializer->deserialize($event);
    }

    private function initializeExchange()
    {
        $this->channel = $this->connection->channel();
        $this->channel->exchange_declare($this->exchangeName, 'fanout', false, false, false);
        $this->channel->queue_declare($this->queueName, false, false, false, false);
    }
}

