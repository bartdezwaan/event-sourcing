<?php

namespace Broadway\CommandHandling;

use Broadway\Serializer\SerializerInterface;

class RabbitMQCommandServer
{
    const QUEUE_NAME = 'command_queue';
    const EXCHANGE_NAME = 'command_handling';

    /**
     * @var SerializerInterface
     */
    private $serializer;

    private $connection;

    private $channel;

    private $bindingKey;

    /**
     * @param SerializerInterface  $serializer
     * @param AMQPStreamConnection $connection
     */
    public function __construct(SerializerInterface $serializer, AMQPStreamConnection $connection, $bindingKey = '#')
    {
        $this->serializer = $serializer;
        $this->connection = $connection;
        $this->bindingKey = $bindingKey;
        $this->init();
    }

    public function listen(array $commandHandlers)
    {
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
        $this->channel->queue_declare(self::QUEUE_NAME, false, false, false, false);
        $this->channel->queue_bind(self::QUEUE_NAME, self::EXCHANGE_NAME, $this->bindingKey);
    }
}

