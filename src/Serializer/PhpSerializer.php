<?php

namespace Zwaan\EventSourcing\Serializer;

use Broadway\Domain\DomainMessage;
use Broadway\Serializer\SerializerInterface;

class PhpSerializer implements SerializerInterface
{
    /**
     * {@inheritDoc}
     */
    public function serialize($object)
    {
        return array(
            'class' => get_class($object),
            'payload' => serialize($object)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function deserialize(array $serializedObject)
    {
        return unserialize($serializedObject['payload']);
    }
}

