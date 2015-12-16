<?php

namespace Zwaan\EventSourcing\Bundle\EventSourcingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EventRequesterCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('zwaan:event:request')
            ->setDescription('Start message listener')
            ->addArgument(
                'queue',
                InputArgument::REQUIRED,
                'The queue to which events should be published'
            )
            ->addArgument(
                'domain',
                InputArgument::REQUIRED,
                'The domain from which you like to receive events'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = $input->getArgument('queue');
        $domain = $input->getArgument('domain');

        $requester = $this->getContainer()->get('zwaan.replay.event_requester');
        $requester->replayEvents($queue, $domain);
    }
}

