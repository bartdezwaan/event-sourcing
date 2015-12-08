<?php

namespace Zwaan\EventSourcing\Replay;

class RabbitMQReplayRequestHandler
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
     * @var EventReplayer
     */
    private $eventReplayer;

    /**
     * @param SerializerInterface           $serializer
     * @param EventBusInterface             $eventBus
     * @param PhpAmqpLibQueueAdapterFactory $queueFactory
     * @param EventReplayer                 $eventReplayer
     */
    public function __construct(SerializerInterface $serializer, EventBusInterface $eventBus, PhpAmqpLibQueueAdapterFactory $queueFactory, EventReplayer $eventReplayer)
    {
        $this->serializer    = $serializer;
        $this->eventBus      = $eventBus;
        $this->queueFactory  = $queueFactory;
        $this->eventReplayer = $eventReplayer;
    }

    /**
     * Push events to a rabbitmq queue.
     *
     * @param string $queueName
     */
    public function replayTo($queueName)
    {
        $events  = $this->eventReplayer->events();
        $adapter = $this->queueFactory->create($queueName);

        foreach ($events as $domainMessage) {
            $msg = json_encode($this->serializer->serialize($domainMessage));
            $adapter->publish($msg);
        }
    }
}

