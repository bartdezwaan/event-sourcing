<?php

namespace Zwaan\EventSourcing\Bundle\EventSourcingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandBusListenCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('zwaan:commandbus:listen')
            ->setDescription('Start command listener');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandBus = $this->getContainer()->get('zwaan.command.bus');
        $commandBus->listen();
    }
}

