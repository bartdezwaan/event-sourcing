<?php

namespace Zwaan\EventSourcing\Replay;

use GuzzleHttp\Client;

class EventRequester
{
    private $client;
    private $replayListener;
    /**
     * @var Helper
     */
    private $preReplayHelpers = [];

    public function __construct(Client $client, ReplayListener $replayListener)
    {
        $this->client = $client;
        $this->replayListener = $replayListener;
    }

    /**
     * @param Helper $helper
     */
    public function subscribePreReplayHelper(Helper $helper)
    {
        $this->preReplayHelpers[] = $helper;
    }

    public function getEventsFrom($sender, $endPoint)
    {
        $this->preReplay();
        $this->triggerReplay($endPoint);
        $this->replayListener->listen($sender);
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

    private function triggerReplay($endpoint)
    {
        $response = $this->client->request('GET', $endpoint);
    }
}

