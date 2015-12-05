<?php

namespace Zwaan\EventSourcing\Bundle\EventSourcingBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class ZwaanEventSourcingExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->setAlias(
            'broadway.event_handling.event_bus',
            'zwaan.event_handling.event_bus'
        );

        $container->setAlias(
            'broadway.serializer.payload',
            'zwaan.serializer'
        );

        $container->setAlias(
            'broadway.serializer.metadata',
            'zwaan.serializer'
        );
    }
}
