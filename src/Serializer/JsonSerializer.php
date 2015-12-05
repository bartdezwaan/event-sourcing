<?php

namespace BartdeZwaan\EventSourcing\Async\Serializer;

use Broadway\Domain\DomainMessage;
use Zumba\Util\JsonSerializer as ZumbaJsonSerializer;

class JsonSerializer implements Serializer
{
    /**
     * @var ZumbaJsonSerializer
     */
    private $serializer;

    /**
     * @var EventObjectMapper
     */
    private $objectMapper;

    /**
     * @param ZumbaJsonSerializer $serializer
     * @param EventObjectMapper   $objectMapper
     */
    public function __construct(ZumbaJsonSerializer $serializer, EventObjectMapper $objectMapper = null)
    {
        $this->serializer   = $serializer;
        $this->objectMapper = $objectMapper;
    }

    /**
     * {@inheritDoc}
     */
    public function serialize(DomainMessage $domainMessage)
    {
        return $this->serializer->serialize($domainMessage, 'json');
    }

    /**
     * {@inheritDoc}
     */
    public function deserialize($data)
    {
        if ($this->objectMapper) {
            $this->objectMapper->map($data);
        }

        return $this->serializer->unserialize($data);
    }
}

