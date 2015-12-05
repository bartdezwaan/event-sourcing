<?php

namespace BartdeZwaan\EventSourcing\Async\Serializer;

class EventObjectMapper
{
    /**
     * @var array
     */
    private $eventObjects = [];

    /**
     * @param string $key
     * @param string $className
     */
    public function addEventObject($key, $className)
    {
        $this->eventObjects[$key] = $className;
    }

    public function map($serializedEvent)
    {
        foreach ($this->eventObjects as $old => $new) {
            str_replace($old, $new, $serializedEvent);
        }

        return $serializedEvent;
    }
}

