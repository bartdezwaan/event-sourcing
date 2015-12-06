<?php

namespace Zwaan\EventSourcing\EventHandling;

use Zwaan\EventSourcing\MessageHandling\RabbitMQ\Adapter;
use Broadway\Domain\DomainMessage;
use Broadway\Serializer\SerializerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMQEventHandler implements EventHandler
{
    /**
     * @param Adapter             $adapter
     * @param SerializerInterface $serializer
     */
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
}

