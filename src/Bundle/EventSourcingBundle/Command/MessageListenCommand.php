<?php

namespace Zwaan\EventSourcing\Bundle\EventSourcingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MessageListenCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('zwaan:message:listen')
            ->setDescription('Start message listener')
            ->addArgument(
                'exchange',
                InputArgument::REQUIRED,
                'To which domain exchange do you want to listen?'
            )
            ->addArgument(
                'queue',
                InputArgument::REQUIRED,
                'What should the name of your queue be?'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $exchange = $input->getArgument('exchange');
        $queue = $input->getArgument('queue');
        $messageListener = $this->getContainer()->get('zwaan.message_listener');
        $messageListener->listen($exchange, $queue);
    }
}

