<?php

namespace Zwaan\EventSourcing\Replay;

use GuzzleHttp\Client;

class EventRequester
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var ReplayListener
     */
    private $replayListener;
    /**
     * @var Helper
     */
    private $preReplayHelpers = [];

    /**
     * @param Client         $client
     * @param ReplayListener $replayListener
     */
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

    /**
     * @param mixed $toAddress   The address to which the events should be send
     * @param mixed $endPoint    The endpoint on which to trigger replaying
     * @param array $projections The projections that replaying should be applied to. Empty value means all.
     */
    public function replayEvents($toAddress, $endPoint, array $projections = array())
    {
        $this->preReplay();
        $this->triggerReplay($toAddress, $endPoint);
        $this->replayListener->listen($toAddress);
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

    /**
     * @param mixed  $sender
     * @param string $endpoint
     */
    private function triggerReplay($toAddress, $endpoint)
    {
        $response = $this->client->request('POST', $endpoint, [
            'form_params' => [
                'response_address' => $toAddress
            ]
        ]);
    }
}

