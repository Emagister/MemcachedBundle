<?php

namespace Emagister\Bundle\MemcachedBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class EmagisterMemcachedExtension extends Extension
{
    /**
     * Loads a specific configuration.
     *
     * @param array            $configs    An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws InvalidArgumentException When provided tag is not defined in this extension
     *
     * @api
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('memcached_defaults.xml');

        $mainConfig = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($mainConfig, $configs);

        if (isset($config['instances'])) {
            if (!$container->hasParameter('emagister_memcached.configurations')) {
                $container->setParameter('emagister_memcached.configurations', array());
            }

            $configurations = $container->getParameter('emagister_memcached.configurations');
            $container->setParameter('emagister_memcached.configurations.default', $config['instances']);

            $configurations[] = 'default';
            $container->setParameter('emagister_memcached.configurations', $configurations);
        }
    }
}