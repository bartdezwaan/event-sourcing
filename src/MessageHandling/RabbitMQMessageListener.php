<?php

namespace Zwaan\EventSourcing\MessageHandling;

use Zwaan\EventSourcing\MessageHandling\RabbitMQ\PhpAmqpLibFanoutAdapterFactory;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBusInterface;
use Broadway\EventHandling\EventListenerInterface;
use Broadway\Serializer\SerializerInterface;

class RabbitMQMessageListener
{
    /**
     * @var array
     */
    private $eventListeners = array();

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var PhpAmqpLibFanoutAdapterFactory
     */
    private $fanoutFactory;

    /**
     * @param SerializerInterface            $serializer
     * @param PhpAmqpLibFanoutAdapterFactory $fanoutFactory
     */
    public function __construct(SerializerInterface $serializer, PhpAmqpLibFanoutAdapterFactory $fanoutFactory)
    {
        $this->serializer    = $serializer;
        $this->fanoutFactory = $fanoutFactory;
    }

    /**
     * @param string $exchangeName
     * @param string $queueName
     */
    public function listen($exchangeName, $queueName)
    {
        $adapter = $this->fanoutFactory->create($exchangeName, $queueName);

        $callback = function($msg) {
            $domainMessage = $this->deserializeDomainMessage($msg->body);
            $this->handle($domainMessage);
        };

        $adapter->listen($callback);
    }

    /**
     * @param EventListenerInterface $eventListener
     */
    public function subscribe(EventListenerInterface $eventListener)
    {
        $this->eventListeners[] = $eventListener;
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

    /**
     * Handle domain message
     */
    public function handle(DomainMessage $domainMessage)
    {
        foreach ($this->eventListeners as $eventListener) {
            $eventListener->handle($domainMessage);
        }
    }
}

