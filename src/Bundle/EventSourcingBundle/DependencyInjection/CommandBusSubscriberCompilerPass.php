<?php

namespace Zwaan\EventSourcing\Bundle\EventSourcingBundle\DependencyInjection;

use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CommandBusSubscriberCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->findDefinition('zwaan.command.bus');

        foreach ($container->findTaggedServiceIds('command_handler') as $id => $attributes) {
            $definition->addMethodCall('subscribe', array(new Reference($id)));
        }
    }
}

