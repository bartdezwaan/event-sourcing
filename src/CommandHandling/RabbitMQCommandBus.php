<?php

namespace Broadway\CommandHandling;

use Broadway\CommandHandling\CommandHandlerInterface;

class RabbitMQCommandBus
{
    private $commandDispatcher;
    private $commandServer;
    private $commandHandlers = [];

    /**
     * @param RabbitMQCommandDispatcher $commandDispatcher
     * @param RabbitMQCommandServer     $commandServer
     */
    public function __construct(RabbitMQCommandDispatcher $commandDispatcher, RabbitMQCommandServer $commandServer)
    {
        $this->commandDispatcher = $commandDispatcher;
        $this->commandServer = $commandServer;
    }

    public function subscribe(CommandHandlerInterface $commandHandler)
    {
        $this->commandHandlers[] = $commandHandler;
    }

    public function dispatch($command)
    {
        $this->commandDispatcher->dispatch($command);
    }

    public function listen()
    {
        $this->commandServer->listen($this->commandHandlers);
    }
}

