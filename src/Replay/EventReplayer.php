<?php

namespace Zwaan\EventSourcing\Replay;

class EventReplayer
{
    private $preReplayHandlers = [];

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

    public function subscribePreReplayHandler(HandlerInterface $handler)
    {
        $this->preReplayHandlers[] = $handler;
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
        foreach ($this->preReplayHandlers as $handler) {
            $handler->handle();
        }
    }
}

