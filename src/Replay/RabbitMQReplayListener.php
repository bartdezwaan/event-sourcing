<?php

namespace Zwaan\EventSourcing\Replay;

use Zwaan\EventSourcing\MessageHandling\RabbitMQ\PhpAmqpLibQueueAdapterFactory;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBusInterface;
use Broadway\Serializer\SerializerInterface;

class RabbitMQReplayListener implements ReplayListener
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var EventBusInterface
     */
    private $eventBus;

    /**
     * @var PhpAmqpLibQueueAdapterFactory
     */
    private $queueFactory;

    /**
     * @param SerializerInterface           $serializer
     * @param EventBusInterface             $eventBus
     * @param PhpAmqpLibQueueAdapterFactory $queueFactory
     */
    public function __construct(SerializerInterface $serializer, EventBusInterface $eventBus, PhpAmqpLibQueueAdapterFactory $queueFactory)
    {
        $this->serializer   = $serializer;
        $this->eventBus     = $eventBus;
        $this->queueFactory = $queueFactory;
    }

    /**
     * @param string $exchangeName
     * @param string $queueName
     */
    public function listen($queueName)
    {
        $adapter = $this->queueFactory->create($queueName);

        $eventBus = $this->eventBus;
        $callback = function($msg) use ($eventBus){
            $this->eventBus->publish($this->getDomainEventStream($msg->body));
        };

        $adapter->listen($callback);
    }

    /**
     * @param string $message
     *
     * @return DomainEventStream
     */
    private function getDomainEventStream($message)
    {
        $domainEvent = $this->deserializeDomainMessage($message);
        $domainEventStream = new DomainEventStream([$domainEvent]);

        return $domainEventStream;
    }

    /**
     * @param string $message
     *
     * @return DomainMessage
     */
    private function deserializeDomainMessage($message)
    {
        return $this->serializer->deserialize(json_decode($message, true));
    }
}
