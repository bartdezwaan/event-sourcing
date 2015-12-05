<?php

namespace Zwaan\EventSourcing\Bundle\EventSourcingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EventBusListenCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('eventbus:listen')
            ->setDescription('Start event listener');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $eventBus = $this->getContainer()->get('broadway.event_handling.event_bus');
        $eventBus->listen();
    }
}

