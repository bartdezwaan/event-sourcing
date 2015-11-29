<?php

namespace BartdeZwaan\EventSourcing\Async\Serializer;

use Broadway\Domain\DomainMessage;

interface Serializer
{
    /**
     * @param DomainMessage $domainMessage
     *
     * @return mixed
     */
    public function serialize(DomainMessage $domainMessage);

    /**
     * @param mixed $data
     *
     * @return DomainMessage
     */
    public function deserialize($data);
}

