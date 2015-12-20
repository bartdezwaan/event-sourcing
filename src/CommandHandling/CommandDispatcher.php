<?php

namespace Zwaan\EventSourcing\CommandHandling;

interface CommandDispatcher
{
    /**
     * Dispatch a command.
     *
     * @param mixed $command
     */
    public function dispatch($command);
}

