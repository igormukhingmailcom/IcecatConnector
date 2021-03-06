<?php

namespace Pim\Bundle\IcecatConnectorBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class PimIcecatConnectorExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('form_types.yml');
        $loader->load('jobs.yml');
        $loader->load('mass_actions.yml');
        $loader->load('parameters.yml');
        $loader->load('processors.yml');
        $loader->load('readers.yml');
        $loader->load('services.yml');
        $loader->load('steps.yml');
        $loader->load('writers.yml');
    }
}
