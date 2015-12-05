<?php

namespace BartdeZwaan\EventSourcing\Async\Serializer;

use BartdeZwaan\EventSourcing\Async\TestCase;
use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use DateTime as BaseDateTime;
use Zumba\Util\JsonSerializer as ZumbaJsonSerializer;

class JMSSerializerTest extends TestCase
{
    private $serializer;

    public function setUp()
    {
        $this->serializer = new JsonSerializer(new ZumbaJsonSerializer());
    }

    /**
     * @test
     */
    public function can_serialize_domain_event()
    {
        $domainMessage = $this->createDomainMessage(array('foo' => 'bar'));
        $serializedEvent = $this->serializer->serialize($domainMessage);

        $this->assertEquals($this->getSerializedEvent(), $serializedEvent);
    }

    /**
     * @test
     */
    public function can_deserialize_domain_event()
    {
        $serializedEvent = $this->getSerializedEvent();
        $domainMessage = $this->createDomainMessage(array('foo' => 'bar'));
        $desserializedEvent = $this->serializer->deserialize($serializedEvent);

        $this->assertEquals($domainMessage, $desserializedEvent);
    }

    /**
     * @test
     */
    public function can_deserialize_unkown_class()
    {
        $objectMapper = new EventObjectMapper();
        $objectMapper->addEventObject('Unkown\Domain\DomainMessage', 'Broadway\Domain\DomainMessage');

        $serializedEvent = $this->getSerializedEvent();
        $objectMapper->map($serializedEvent);

        $domainMessage = $this->createDomainMessage(array('foo' => 'bar'));
        $desserializedEvent = $this->serializer->deserialize($serializedEvent);

        $this->assertEquals($domainMessage, $desserializedEvent);
    }

    private function createDomainMessage($payload)
    {
        return new DomainMessage(1, 1, new Metadata(array()), new SimpleTestEvent($payload), DateTime::fromString('2015-11-29 18:24:55.072250'));
    }

    public function getSerializedEvent()
    {
        return '{"@type":"Broadway\\\\Domain\\\\DomainMessage","playhead":1,"metadata":{"@type":"Broadway\\\\Domain\\\\Metadata","values":[]},"payload":{"@type":"BartdeZwaan\\\\EventSourcing\\\\Async\\\\Serializer\\\\SimpleTestEvent","data":{"foo":"bar"}},"id":1,"recordedOn":{"@type":"Broadway\\\\Domain\\\\DateTime","dateTime":{"@type":"DateTime","date":"2015-11-29 18:24:55.072250","timezone_type":3,"timezone":"Europe\/Berlin"}}}';
    }

    public function getUnkownSerializedEvent()
    {
        return '{"@type":"Unkown\\\\Domain\\\\DomainMessage","playhead":1,"metadata":{"@type":"Broadway\\\\Domain\\\\Metadata","values":[]},"payload":{"@type":"BartdeZwaan\\\\EventSourcing\\\\Async\\\\Serializer\\\\SimpleTestEvent","data":{"foo":"bar"}},"id":1,"recordedOn":{"@type":"Broadway\\\\Domain\\\\DateTime","dateTime":{"@type":"DateTime","date":"2015-11-29 18:24:55.072250","timezone_type":3,"timezone":"Europe\/Berlin"}}}';
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

