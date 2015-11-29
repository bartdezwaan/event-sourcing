<?php

namespace BartdeZwaan\EventSourcing\Async\EventHandling;

use BartdeZwaan\EventSourcing\Async\Serializer\PhpSerializer;
use BartdeZwaan\EventSourcing\Async\TestCase;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListenerInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class AsyncEventBusTest extends TestCase
{
    private $eventBus;
    private $messageHandler;

    public function setUp()
    {
        $this->eventBus = new AsyncEventBus($this->getMessageHandler());
    }

    private function getMessageHandler()
    {
        if (! $this->messageHandler) {
            $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
            $serializer = new PhpSerializer();
            $this->messageHandler = new AsyncMessageHandler('testExchange', 'events_queue', $connection, $serializer);
        }

        return $this->messageHandler;
    }

    /**
     * @test
     */
    public function it_subscribes_an_event_listener()
    {
        $domainMessage = $this->createDomainMessage(array('foo' => 'bar'));

        $eventListener = $this->createEventListenerMock();
        $eventListener
            ->expects($this->once())
            ->method('handle')
            ->with($domainMessage);

        $this->eventBus->subscribe($eventListener);
        $this->eventBus->publish(new DomainEventStream(array($domainMessage)));
        $this->eventBus->listen();
    }

    private function createEventListenerMock()
    {
        return $this->getMockBuilder('Broadway\EventHandling\EventListenerInterface')->getMock();
    }

    private function createDomainMessage($payload)
    {
        return DomainMessage::recordNow(1, 1, new Metadata(array()), new SimpleEventBusTestEvent($payload));
    }
}

class SimpleEventBusTestEvent
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }
}

class AsyncMessageHandler extends RabbitMQMessageHandler
{
    /**
     * {@inheritDoc}
     */
    public function listen(AsyncEventBus $eventBus)
    {
        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $this->channel = $connection->channel();
        $this->channel->queue_bind($this->queueName, $this->exchangeName);

        $callback = function($msg) use ($eventBus){
            $eventBus->handle(unserialize($msg->body));
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };

        $this->channel->basic_consume($this->queueName, '', false, false, false, false, $callback);
        while(count($this->channel->callbacks)) {
            $this->channel->wait();
            $this->channel->callbacks = [];
        }

        $this->channel->close();
        $this->connection->close();
    }
}

