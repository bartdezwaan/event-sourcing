<?php

namespace Zwaan\EventSourcing\MessageHandling;

use Zwaan\EventSourcing\MessageHandling\RabbitMQ\PhpAmqpLibFanoutAdapterFactory;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBusInterface;
use Broadway\Serializer\SerializerInterface;

class RabbitMQMessageListener
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
     * @var PhpAmqpLibFanoutAdapterFactory
     */
    private $fanoutFactory;

    /**
     * @param SerializerInterface            $serializer
     * @param EventBusInterface              $eventBus
     * @param PhpAmqpLibFanoutAdapterFactory $fanoutFactory
     */
    public function __construct(SerializerInterface $serializer, EventBusInterface $eventBus, PhpAmqpLibFanoutAdapterFactory $fanoutFactory)
    {
        $this->serializer    = $serializer;
        $this->eventBus      = $eventBus;
        $this->fanoutFactory = $fanoutFactory;
    }

    /**
     * @param string $exchangeName
     * @param string $queueName
     */
    public function listen($exchangeName, $queueName)
    {
        $adapter = $this->fanoutFactory->create($exchangeName, $queueName);

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

