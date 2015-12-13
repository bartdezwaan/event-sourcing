<?php

namespace Zwaan\EventSourcing\Replay;

class EventReplayer
{
    /**
     * @var EventRepository
     */
    private $eventRepository;

    /**
     * @param ReplayRequestHandler $queueFactory
     * @param EventRepository      $eventRepository
     */
    public function __construct(ReplayRequestHandler $replayRequestHandler, EventRepository $eventRepository)
    {
        $this->replayRequestHandler = $replayRequestHandler;
        $this->eventRepository      = $eventRepository;
    }

    /**
     * @param Helper $helper
     */
    public function subscribePreReplayHelper(Helper $helper)
    {
        $this->preReplayHelpers[] = $helper;
    }

    /**
     * Push events to a receiver
     *
     * @param mixed $receiver
     */
    public function replayTo($receiver)
    {
        $events = $this->eventRepository->events();
        $this->replayRequestHandler->replay($events, $receiver);
    }
}

