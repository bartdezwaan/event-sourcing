<?php

namespace Zwaan\EventSourcing\Replay;

interface Helper
{
    /**
     * Execute task before replaying.
     */
    public function execute();
}

