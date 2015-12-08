<?php

namespace Zwaan\EventSourcing\Replay;

use Broadway\Serializer\SimpleInterfaceSerializer;
use Broadway\Serializer\SerializableInterface;
use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventStore\DBALEventStore;
use Doctrine\DBAL\DriverManager;
use Rhumsaa\Uuid\Uuid;
use Zwaan\EventSourcing\TestCase;

class EventReplayerTest extends TestCase
{
    private $eventStore;
    private $replayer;

    public function setUp()
    {
        $connection       = DriverManager::getConnection(array('driver' => 'pdo_sqlite', 'memory' => true));
        $schemaManager    = $connection->getSchemaManager();
        $schema           = $schemaManager->createSchema();
        $this->eventStore = new DBALEventStore($connection, new SimpleInterfaceSerializer(), new SimpleInterfaceSerializer(), 'events');
        $this->replayer = new EventReplayer($connection, new SimpleInterfaceSerializer(), new SimpleInterfaceSerializer(), 'events');

        $table = $this->eventStore->configureSchema($schema);
        $schemaManager->createTable($table);

        $id = Uuid::uuid4();
        $domainEventStream = new DomainEventStream(array(
            $this->createDomainMessage($id, 0),
            $this->createDomainMessage($id, 1),
            $this->createDomainMessage($id, 2),
            $this->createDomainMessage($id, 3),
        ));

        $this->eventStore->append($id, $domainEventStream);
    }

    protected function createDomainMessage($id, $playhead, $recordedOn = null)
    {
        return new DomainMessage($id, $playhead, new MetaData(array()), new Event(), $recordedOn ? $recordedOn : DateTime::now());
    }

    /**
     * @test
     */
    public function can_retrieve_all_events()
    {
        $count = 0;
        $events = $this->replayer->events();
        $playedEvents = [];

        foreach ($events as $event) {
            $playedEvents[] = $event;
            $count++;
        }

        $this->assertEquals(5, $count);
        $this->assertEquals(EventReplayer::END, $playedEvents[4]);
    }
}

class Event implements SerializableInterface
{
    public static function deserialize(array $data)
    {
        return new Event();
    }

    public function serialize()
    {
        return array();
    }
}

