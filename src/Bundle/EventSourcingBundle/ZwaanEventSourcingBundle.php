<?php

namespace Zwaan\EventSourcing\Bundle\EventSourcingBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Zwaan\EventSourcing\Bundle\EventSourcingBundle\DependencyInjection\CommandBusSubscriberCompilerPass;
use Zwaan\EventSourcing\Bundle\EventSourcingBundle\DependencyInjection\ReplayListenerCompilerPass;

class ZwaanEventSourcingBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new CommandBusSubscriberCompilerPass());
        $container->addCompilerPass(new ReplayListenerCompilerPass());
    }
}
