<?php

namespace Zwaan\EventSourcing\Replay;

use Broadway\Serializer\SimpleInterfaceSerializer;
use Broadway\Serializer\SerializableInterface;
use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Rhumsaa\Uuid\Uuid;
use Zwaan\EventSourcing\MessageHandling\RabbitMQ\InMemoryAdapter;
use Zwaan\EventSourcing\Serializer\PhpSerializer;
use Zwaan\EventSourcing\TestCase;

class RabbitMQReplayRequestHandlerTest extends TestCase
{
    private $eventRepository;
    private $queueFactory;
    private $replayRequestHandler;

    public function setUp()
    {
        $serializer = new PhpSerializer();
        $this->replayRequestHandler = new RabbitMQReplayRequestHandler(
            $serializer,
            $this->getQueueAdapterFactoryMock(),
            $this->getEventRepositoryMock()
        );
    }

    /**
     * @test
     */
    public function can_replay_to_queue()
    {
        $adapter = new InMemoryAdapter();
        $queueFactory = $this->getQueueAdapterFactoryMock()
            ->expects($this->once())
            ->method('create')
            ->will($this->returnValue($adapter));

        $eventRepository = $this->getEventRepositoryMock()
            ->expects($this->once())
            ->method('events')
            ->will($this->returnValue($this->getDomainMessages()));

        $this->replayRequestHandler->replayTo('aQueueName');

        $this->assertCount(5, $adapter->queue());
        $this->assertEquals('finished', $adapter->queue()[4]->body);
    }

    private function getEventRepositoryMock()
    {
        if (! $this->eventRepository) {
            $this->eventRepository = $this->getMockBuilder('Zwaan\EventSourcing\Replay\EventRepository')
                ->disableOriginalConstructor()
                ->getMock();
        }

        return $this->eventRepository;
    }

    private function getQueueAdapterFactoryMock()
    {
        if (! $this->queueFactory) {
            $this->queueFactory = $this->getMockBuilder('Zwaan\EventSourcing\MessageHandling\RabbitMQ\PhpAmqpLibQueueAdapterFactory')
                ->disableOriginalConstructor()
                ->getMock();
        }

        return $this->queueFactory;
    }

    private function getDomainMessages()
    {
        $id = Uuid::uuid4();
        return array(
            $this->createDomainMessage($id, 0),
            $this->createDomainMessage($id, 1),
            $this->createDomainMessage($id, 2),
            $this->createDomainMessage($id, 3)
        );
    }

    protected function createDomainMessage($id, $playhead, $recordedOn = null)
    {
        return new DomainMessage($id, $playhead, new MetaData(array()), new Event(), $recordedOn ? $recordedOn : DateTime::now());
    }

}

