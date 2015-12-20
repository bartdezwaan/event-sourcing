<?php

namespace Zwaan\EventSourcing\Bundle\EventSourcingBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class ReplayListenerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (! $container->has('zwaan.replay.event_bus')) {
            return;
        }

        $definition = $container->findDefinition(
            'zwaan.replay.event_bus'
        );

        $taggedServices = $container->findTaggedServiceIds(
            'replay_listener'
        );
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                'subscribe',
                array(new Reference($id))
            );
        }
    }
}

