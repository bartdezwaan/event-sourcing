<?php

namespace BartdeZwaan\EventSourcing\Async\EventHandling;

use BartdeZwaan\EventSourcing\Async\Serializer\JsonSerializer;
use BartdeZwaan\EventSourcing\Async\TestCase;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListenerInterface;
use Broadway\EventHandling\EventBusInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Zumba\Util\JsonSerializer as ZumbaJsonSerializer;

class RabbitMQEventListenerTest extends TestCase
{
    private $eventListener;
    private $eventBus;
    private $connection;
    private $serializer;

    public function setUp()
    {
        $this->connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $this->serializer = new JsonSerializer(new ZumbaJsonSerializer());
        $this->eventListener = new ReturningRabbitMQEventListener('testExchange', 'events_queue', $this->connection, $this->serializer, $this->getEventBusMock());
    }

    private function getEventBusMock()
    {
        if (false == $this->eventBus) {
            $this->eventBus = $this->getMock('Broadway\EventHandling\EventBusInterface');
        }

        return $this->eventBus;
    }

    /**
     * @test
     */
    public function it_can_listen_for_an_event_and_publish_it_to_event_bus()
    {
        $msg = new AMQPMessage($this->serializer->serialize($this->createDomainMessage(array('foo' => 'bar'))));

        $this->channel = $this->connection->channel();
        $this->channel->exchange_declare('testExchange', 'fanout', false, false, false);
        $this->channel->queue_declare('events_queue', false, false, false, false);
        $this->channel->basic_publish($msg, 'testExchange');

        $this->getEventBusMock()
            ->expects($this->once())
            ->method('publish');
        $this->eventListener->listen();
    }

    private function createDomainMessage($payload)
    {
        return DomainMessage::recordNow(1, 1, new Metadata(array()), new SimpleTestEvent($payload));
    }
}

class ReturningRabbitMQEventListener extends RabbitMQEventListener
{
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
            $this->channel->callbacks = [];
        }

        $this->channel->close();
        $this->connection->close();
    }
}

class SimpleTestEvent
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }
}

