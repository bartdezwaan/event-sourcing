<?php

namespace Zwaan\EventSourcing\Replay;

use Zwaan\EventSourcing\TestCase;

class EventRequesterTest extends TestCase
{
    private $client;
    private $replayListener;
    private $eventRequester;

    public function setUp()
    {
        $this->eventRequester = new EventRequester($this->getClientMock(), $this->getReplayListenerMock());
    }

    private function getClientMock()
    {
        if (! $this->client) {
            $this->client = $this->getMockBuilder('GuzzleHttp\Client')
                ->disableOriginalConstructor()
                ->getMock();
        }

        return $this->client;
    }

    private function getReplayListenerMock()
    {
        if (! $this->replayListener) {
            $this->replayListener = $this->getMockBuilder('Zwaan\EventSourcing\Replay\ReplayListener')
                ->disableOriginalConstructor()
                ->getMock();
        }

        return $this->replayListener;
    }

    /**
     * @test
     */
    public function helpers_will_be_triggered_before_replaying()
    {
        $helper1 = $this->getHelperMock();
        $helper1
            ->expects($this->once())
            ->method('execute');
        $helper2 = $this->getHelperMock();
        $helper2
            ->expects($this->once())
            ->method('execute');

        $this->eventRequester->subscribePreReplayHelper($helper1);
        $this->eventRequester->subscribePreReplayHelper($helper2);

        $this->eventRequester->replayEvents('queueName', 'www.domain.local');
    }

    private function getHelperMock()
    {
        return $this->getMock('Zwaan\EventSourcing\Replay\Helper');
    }
}

