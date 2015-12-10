<?php

namespace Zwaan\EventSourcing\Replay;

use Broadway\Serializer\SerializerInterface;
use Zwaan\EventSourcing\MessageHandling\RabbitMQ\PhpAmqpLibQueueAdapterFactory;

class RabbitMQReplayRequestHandler implements ReplayRequestHandler
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var PhpAmqpLibQueueAdapterFactory
     */
    private $queueFactory;

    /**
     * @param SerializerInterface           $serializer
     * @param PhpAmqpLibQueueAdapterFactory $queueFactory
     */
    public function __construct(SerializerInterface $serializer, PhpAmqpLibQueueAdapterFactory $queueFactory)
    {
        $this->serializer   = $serializer;
        $this->queueFactory = $queueFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function replay($events, $queueName)
    {
        $adapter = $this->queueFactory->create($queueName);

        foreach ($events as $domainMessage) {
            $msg = json_encode($this->serializer->serialize($domainMessage));
            $adapter->publish($msg);
        }

        $adapter->publish('finished');
    }
}

