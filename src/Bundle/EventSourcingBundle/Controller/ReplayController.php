<?php

namespace Zwaan\EventSourcing\Bundle\EventSourcingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ReplayController extends Controller
{
    public function indexAction(Request $request)
    {
        $receiver = $request->request->get('receiver');
        $response = new JsonResponse();

        if (false == $receiver) {
            $response->setData(array(
                'status' => 'error',
                'message' => 'No receiver given'
            ));

            return $response;
        }

        try {
            $publisher = $this->container->get('zwaan.replay.event_replayer');
            $publisher->replayTo('replay_queue');
            $response->setData(array(
                'status' => 'success'
            ));
        } catch (\Exception $e) {
            $response->setData(array(
                'status' => 'error',
                'message' => $e->getMessage()
            ));
        }

        return $response;
    }
}

