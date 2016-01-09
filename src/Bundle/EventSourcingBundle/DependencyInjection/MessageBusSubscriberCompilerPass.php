<?php

namespace Zwaan\EventSourcing\Bundle\EventSourcingBundle\DependencyInjection;

use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class MessageBusSubscriberCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->findDefinition('zwaan.message_listener');

        foreach ($container->findTaggedServiceIds('zwaan.message_listener') as $id => $attributes) {
            $definition->addMethodCall('subscribe', array(new Reference($id)));
        }
    }
}

