<?php

namespace Zwaan\EventSourcing\Replay;

class EventReplayer
{
    /**
     * @var Helper
     */
    private $preReplayHelpers = [];

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
        $this->preReplay();
        $events = $this->eventRepository->events();
        $this->replayRequestHandler->replay($events, $receiver);
    }

    /**
     * Execute pre replay tasks.
     */
    private function preReplay()
    {
        foreach ($this->preReplayHelpers as $helper) {
            $helper->execute();
        }
    }
}

