<?php

namespace BartdeZwaan\EventSourcing\Async\Serializer;

use Broadway\Domain\DomainMessage;

class PhpSerializer implements Serializer
{
    /**
     * {@inheritDoc}
     */
    public function serialize(DomainMessage $domainMessage)
    {
        return serialize($domainMessage);
    }

    /**
     * {@inheritDoc}
     */
    public function deserialize($data)
    {
        return unserialize($data);
    }
}

