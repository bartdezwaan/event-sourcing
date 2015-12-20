<?php

namespace Broadway\CommandHandling;

use Broadway\CommandHandling\Exception\CommandNotAnObjectException;
use Broadway\Serializer\SerializerInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQCommandDispatcher implements CommandDispatcher
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    private $connection;

    private $channel;

    private $response;

    /**
     * @param SerializerInterface  $serializer
     * @param AMQPStreamConnection $connection
     */
    public function __construct(SerializerInterface $serializer, AMQPStreamConnection $connection)
    {
        $this->serializer = $serializer;
        $this->connection = $connection;
        $this->init();
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch($command)
    {
        if (! is_object($command)) {
            throw new CommandNotAnObjectException();
        }

        $serializedCommand = $this->serializer->serialize($command);
        $this->response = null;
        $this->corr_id = uniqid();

        $msg = new AMQPMessage(
            (string) $serializedCommand,
            array(
                'correlation_id' => $this->corr_id,
                'reply_to' => $this->callback_queue
            )
        );

        $this->channel->basic_publish($msg, 'command_handling', 'command');

        while(!$this->response) {
            $this->channel->wait();
        }

        return $this->response;
    }

    private function init()
    {
        $this->channel = $this->connection->channel();
        $this->channel->exchange_declare('command_handling', 'topic', false, false, false);

        list($this->callback_queue, ,) = $this->channel->queue_declare(
            "", false, false, true, false
        );

        $this->channel->basic_consume(
            $this->callback_queue, '', false, false, false, false,
            array($this, 'onResponse')
        );
    }

    public function onResponse($rep)
    {
        if($rep->get('correlation_id') == $this->corr_id) {
            $this->response = $rep->body;
        }
    }
}


