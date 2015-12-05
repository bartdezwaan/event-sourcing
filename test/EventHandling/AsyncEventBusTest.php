<?php

namespace BartdeZwaan\EventSourcing\Async\EventHandling;

use BartdeZwaan\EventSourcing\Async\MessageHandling\RabbitMQ\InMemoryAdapter;
use BartdeZwaan\EventSourcing\Async\Serializer\PhpSerializer;
use BartdeZwaan\EventSourcing\Async\TestCase;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListenerInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class AsyncEventBusTest extends TestCase
{
    private $eventBus;
    private $messageHandler;
    private $serializer;

    public function setUp()
    {
        $this->serializer = new PhpSerializer();
        $this->eventBus = new AsyncEventBus($this->getMessageHandler());
    }

    private function getMessageHandler()
    {
        if (! $this->messageHandler) {
            $adapter = new InMemoryAdapter();
            $this->messageHandler = new RabbitMQMessageHandler($adapter, $this->serializer);
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

    /**
     * @test
     */
    public function it_publishes_events_to_subscribed_event_listeners()
    {
        $domainMessage1 = $this->createDomainMessage(array());
        $domainMessage2 = $this->createDomainMessage(array());

        $domainEventStream = new DomainEventStream(array($domainMessage1, $domainMessage2));

        $eventListener1 = $this->createEventListenerMock();
        $eventListener1
            ->expects($this->at(0))
            ->method('handle')
            ->with($domainMessage1);
        $eventListener1
            ->expects($this->at(1))
            ->method('handle')
            ->with($domainMessage2);

        $eventListener2 = $this->createEventListenerMock();
        $eventListener2
            ->expects($this->at(0))
            ->method('handle')
            ->with($domainMessage1);
        $eventListener2
            ->expects($this->at(1))
            ->method('handle')
            ->with($domainMessage2);

        $this->eventBus->subscribe($eventListener1);
        $this->eventBus->subscribe($eventListener2);
        $this->eventBus->publish($domainEventStream);
        $this->eventBus->listen();
    }

    /**
     * @test
     */
    public function it_should_still_publish_events_after_exception()
    {
        $domainMessage1 = $this->createDomainMessage(array('foo' => 'bar'));
        $domainMessage2 = $this->createDomainMessage(array('foo' => 'bas'));

        $domainEventStream1 = new DomainEventStream(array($domainMessage1));
        $domainEventStream2 = new DomainEventStream(array($domainMessage2));

        $messageHandler = $this->createMessageHandlerMock();
        $messageHandler
            ->expects($this->at(0))
            ->method('publish')
            ->with($domainMessage1)
            ->will($this->throwException(new \Exception('I failed.')));

        $messageHandler
            ->expects($this->at(1))
            ->method('publish')
            ->with($domainMessage2);

        $eventBus = new AsyncEventBus($messageHandler);

        try {
            $eventBus->publish($domainEventStream1);
        } catch (\Exception $e) {
            $this->assertEquals('I failed.', $e->getMessage());
        }

        $eventBus->publish($domainEventStream2);
    }

    /**
     * @test
     */
    public function it_should_still_listen_to_events_after_exception()
    {
        $domainMessage1 = $this->createDomainMessage(array());
        $domainMessage2 = $this->createDomainMessage(array());

        $domainEventStream = new DomainEventStream(array($domainMessage1, $domainMessage2));

        $eventListener1 = $this->createEventListenerMock();
        $eventListener1
            ->expects($this->at(0))
            ->method('handle')
            ->with($domainMessage1)
            ->will($this->throwException(new \Exception('I failed.')));
        $eventListener1
            ->expects($this->at(1))
            ->method('handle')
            ->with($domainMessage2);

        $eventListener2 = $this->createEventListenerMock();
        $eventListener2
            ->expects($this->at(0))
            ->method('handle')
            ->with($domainMessage1);
        $eventListener2
            ->expects($this->at(1))
            ->method('handle')
            ->with($domainMessage2);

        $this->eventBus->subscribe($eventListener1);
        $this->eventBus->subscribe($eventListener2);

        $this->eventBus->publish($domainEventStream);

        try {
            $this->eventBus->listen();
        } catch (\Exception $e) {
            $this->assertEquals('I failed.', $e->getMessage());
        }

        $this->eventBus->listen();
    }

    private function createEventListenerMock()
    {
        return $this->getMockBuilder('Broadway\EventHandling\EventListenerInterface')->getMock();
    }

    private function createMessageHandlerMock()
    {
        return $this->getMockBuilder('BartdeZwaan\EventSourcing\Async\EventHandling\MessageHandler')->getMock();
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

