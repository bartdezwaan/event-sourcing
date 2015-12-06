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
    private $eventBus;
    private $serializer;
    private $fanoutFactory;

    public function setUp()
    {
        $this->serializer      = new PhpSerializer();
        $this->eventBus        = $this->getEventBusMock();
        $this->fanoutFactory   = $this->getFanoutFactoryMock();
        $this->messageListener = new RabbitMQMessageListener($this->serializer, $this->eventBus, $this->fanoutFactory);
    }

    private function getEventBusMock()
    {
        if (false == $this->eventBus) {
            $this->eventBus = $this->getMock('Broadway\EventHandling\EventBusInterface');
        }

        return $this->eventBus;
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

    /**
     * @test
     */
    public function it_can_listen_for_an_event_and_publish_it_to_event_bus()
    {
        $this->getFanoutFactoryMock()
            ->expects($this->once())
            ->method('create')
            ->will($this->returnValue($this->getInMemoryAdapterWithOnePublishedMessage()));

        $this->getEventBusMock()
            ->expects($this->once())
            ->method('publish');

        $this->messageListener->listen('anExchangeName', 'aQueueName');
    }

    private function createDomainMessage($payload)
    {
        return DomainMessage::recordNow(1, 1, new Metadata(array()), new SimpleTestEvent($payload));
    }

    private function getInMemoryAdapterWithOnePublishedMessage()
    {
        $adapter = new InMemoryAdapter();
        $adapter->publish(
            json_encode(
                $this->serializer->serialize(
                    $this->createDomainMessage(
                        array('foo' => 'bar')
                    )
                )
            )
        );

        return $adapter;
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

