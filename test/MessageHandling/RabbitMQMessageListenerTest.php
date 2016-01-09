<?php

namespace Zwaan\EventSourcing\MessageHandling;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListenerInterface;
use Broadway\EventHandling\EventBusInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Zumba\Util\JsonSerializer as ZumbaJsonSerializer;
use Zwaan\EventSourcing\MessageHandling\RabbitMQ\InMemoryAdapter;
use Zwaan\EventSourcing\Serializer\PhpSerializer;
use Zwaan\EventSourcing\TestCase;

class RabbitMQMessageListenerTest extends TestCase
{
    private $messageListener;
    private $serializer;
    private $fanoutFactory;

    public function setUp()
    {
        $this->serializer      = new PhpSerializer();
        $this->fanoutFactory   = $this->getFanoutFactoryMock();
        $this->messageListener = new RabbitMQMessageListener($this->serializer, $this->fanoutFactory);
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

        $this->messageListener->subscribe($eventListener);

        $this->getFanoutFactoryMock()
            ->expects($this->once())
            ->method('create')
            ->will($this->returnValue($this->getInMemoryAdapter([$domainMessage])));

        $this->messageListener->listen('anExchangeName', 'aQueueName');
    }

    /**
     * @test
     */
    public function it_publishes_events_to_subscribed_event_listeners()
    {
        $domainMessage1 = $this->createDomainMessage(array('foo' => 'bar'));
        $domainMessage2 = $this->createDomainMessage(array('foo' => 'bar'));

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

        $this->getFanoutFactoryMock()
            ->expects($this->once())
            ->method('create')
            ->will($this->returnValue($this->getInMemoryAdapter([$domainMessage1, $domainMessage2])));

        $this->messageListener->subscribe($eventListener1);
        $this->messageListener->subscribe($eventListener2);
        $this->messageListener->listen('anExchangeName', 'aQueueName');
    }

    private function getFanoutFactoryMock()
    {
        if (false == $this->fanoutFactory) {
            $this->fanoutFactory = $this->getMockBuilder('Zwaan\EventSourcing\MessageHandling\RabbitMQ\PhpAmqpLibFanoutAdapterFactory')
                ->disableOriginalConstructor()
                ->getMock();
        }

        return $this->fanoutFactory;
    }

    private function createDomainMessage($payload)
    {
        return DomainMessage::recordNow(1, 1, new Metadata(array()), new SimpleTestEvent($payload));
    }

    private function getInMemoryAdapter(array $messages)
    {
        $adapter = new InMemoryAdapter();

        foreach ($messages as $message) {
            $adapter->publish(
                json_encode(
                    $this->serializer->serialize(
                        $message
                    )
                )
            );
        }

        return $adapter;
    }

    private function createEventListenerMock()
    {
        return $this->getMockBuilder('Broadway\EventHandling\EventListenerInterface')->getMock();
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

