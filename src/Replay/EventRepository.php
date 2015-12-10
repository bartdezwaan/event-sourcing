<?php

namespace Zwaan\EventSourcing\Replay;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainEventStreamInterface;
use Broadway\Domain\DomainMessage;
use Broadway\EventStore\Exception\InvalidIdentifierException;
use Broadway\EventStore\Management\Criteria;
use Broadway\EventStore\Management\CriteriaNotSupportedException;
use Broadway\EventStore\Management\EventStoreManagementInterface;
use Broadway\Serializer\SerializerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Version;
use Rhumsaa\Uuid\Uuid;

class EventRepository
{
    private $connection;

    private $payloadSerializer;

    private $metadataSerializer;

    private $loadStatement = null;

    private $tableName;

    private $useBinary;

    /**
     * @param string $tableName
     */
    public function __construct(
        Connection $connection,
        SerializerInterface $payloadSerializer,
        SerializerInterface $metadataSerializer,
        $tableName,
        $useBinary = false
    ) {
        $this->connection         = $connection;
        $this->payloadSerializer  = $payloadSerializer;
        $this->metadataSerializer = $metadataSerializer;
        $this->tableName          = $tableName;
        $this->useBinary          = (bool) $useBinary;

        if ($this->useBinary && Version::compare('2.5.0') >= 0) {
            throw new \InvalidArgumentException(
                'The Binary storage is only available with Doctrine DBAL >= 2.5.0'
            );
        }
    }

    /**
     * Yields DomainMessage
     * 
     * @return Generator
     */
    public function events()
    {
        $statement = $this->prepareLoadStatement();
        $statement->execute();

        $events = array();
        while ($row = $statement->fetch()) {
            $event = $this->deserializeEvent($row);

            yield $event;
        }
    }

    private function convertStorageValueToIdentifier($id)
    {
        if ($this->useBinary) {
            try {
                return Uuid::fromBytes($id)->toString();
            } catch (\Exception $e) {
                throw new InvalidIdentifierException(
                    'Could not convert binary storage value to UUID.'
                );
            }
        }

        return $id;
    }

    private function deserializeEvent($row)
    {
        return new DomainMessage(
            $this->convertStorageValueToIdentifier($row['uuid']),
            $row['playhead'],
            $this->metadataSerializer->deserialize(json_decode($row['metadata'], true)),
            $this->payloadSerializer->deserialize(json_decode($row['payload'], true)),
            DateTime::fromString($row['recorded_on'])
        );
    }

    private function prepareLoadStatement()
    {
        if (null === $this->loadStatement) {
            $query = 'SELECT uuid, playhead, metadata, payload, recorded_on
                FROM ' . $this->tableName . '
                ORDER BY recorded_on ASC';
            $this->loadStatement = $this->connection->prepare($query);
        }

        return $this->loadStatement;
    }
}

