<?php

namespace Zwaan\EventSourcing\CommandHandling;

use Broadway\Serializer\SerializerInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQCommandServer
{
    const EXCHANGE_NAME = 'command_handling';

    /**
     * @var SerializerInterface
     */
    private $serializer;

    private $connection;

    private $channel;

    private $bindingKey;

    private $queueName;

    /**
     * @param SerializerInterface  $serializer
     * @param AMQPStreamConnection $connection
     */
    public function __construct(SerializerInterface $serializer, AMQPStreamConnection $connection, $bindingKey = '#')
    {
        $this->serializer = $serializer;
        $this->connection = $connection;
        $this->bindingKey = $bindingKey;
    }

    public function listen(array $commandHandlers)
    {
        $this->init();

        $callback = function($req) use ($commandHandlers) {
            $command = $this->serializer->deserialize(json_decode($req->body, true));
            foreach ($commandHandlers as $commandHandler) {
                if ($this->hasHandleMethodFor($commandHandler, $command)) {
                    try {
                        $commandHandler->handle($command);
                        $message = json_encode(['status' => 'succes']);
                    } catch (\Exception $e) {
                        $message = json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                    };

                    $msg = new AMQPMessage(
                        (string) $message,
                        array('correlation_id' => $req->get('correlation_id'))
                        );

                    $req->delivery_info['channel']->basic_publish(
                        $msg, '', $req->get('reply_to')
                    );

                    $req->delivery_info['channel']->basic_ack(
                        $req->delivery_info['delivery_tag']
                    );
                }
            }

        };

        $this->channel->basic_consume($this->queueName, '', false, false, false, false, $callback);

        while(count($this->channel->callbacks)) {
            $this->channel->wait();
        }

        $this->channel->close();
        $this->connection->close();
    }

    private function hasHandleMethodFor($commandHandler, $command)
    {
        $method = $this->getHandleMethod($command);

        if (! method_exists($commandHandler, $method)) {
            return false;
        }

        return true;
    }

    private function getHandleMethod($command)
    {
        if (! is_object($command)) {
            throw new CommandNotAnObjectException();
        }

        $classParts = explode('\\', get_class($command));

        return 'handle' . end($classParts);
    }

    private function init()
    {
        $this->channel = $this->connection->channel();
        $this->channel->exchange_declare(self::EXCHANGE_NAME, 'topic', false, false, false);
        echo "create server queue";
        list($this->queueName, ,) = $this->channel->queue_declare("", false, false, true, false);
        $this->channel->queue_bind($this->queueName, self::EXCHANGE_NAME, $this->bindingKey);
    }
}

