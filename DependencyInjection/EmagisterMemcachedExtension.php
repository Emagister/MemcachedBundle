<?php

namespace Emagister\Bundle\MemcachedBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use LogicException;

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
        $loader->load('session.xml');

        $mainConfig = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($mainConfig, $configs);

        if (isset($config['session_support']) && true === $config['session_support']['enabled']) {
            if (!isset($config['instances']) || !isset($config['instances'][$config['session_support']['instance_id']])) {
                throw new \LogicException(sprintf('The instance "%s" does not exist! Cannot enable the session support!', $config['session_support']['instance_id']));
            }

            $type = $config['instances'][$config['session_support']['instance_id']]['type'];
            $options = $config['session_support']['options'];
            $this->enableSessionSupport($type, $config['session_support']['instance_id'], $options, $container);
        }

        if (isset($config['instances'])) {
            $this->addInstances($config['instances'], $container);
        }

        if (isset($config['memcache_options'])) {
            $this->configureMemcache($config['memcache_options']);
        }
    }

    /**
     * Given a handler (memcache/memcached) enables session support
     *
     * @param string $type
     * @param string $instanceId
     * @param array $options
     * @param ContainerBuilder $container
     */
    private function enableSessionSupport($type, $instanceId, array $options, ContainerBuilder $container)
    {
        $definition = $container->findDefinition(sprintf('session.handler.%s', $type));
        $definition
            ->addArgument(new Reference($instanceId))
            ->addArgument($options)
        ;

        $container->setParameter('emagister_memcached.session.handler.type', $type);
        $this->addClassesToCompile(array(
            $definition->getClass()
        ));
    }

    /**
     * Adds memcache/memcached instances to the service contaienr
     *
     * @param array $instances
     * @param ContainerBuilder $container
     *
     * @throws \LogicException
     */
    private function addInstances(array $instances, ContainerBuilder $container)
    {
        foreach ($instances as $instance => $memcachedConfig) {
            $method = 'new' . ucfirst($memcachedConfig['type']) . 'Instance';

            if (!method_exists($this, $method)) {
                throw new \LogicException(sprintf('"%s" not supported', $memcachedConfig['type']));
            }

            $this->{$method}($instance, $memcachedConfig, $container);
        }
    }

    /**
     * Creates a new Memcached definition
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param string $name
     * @param array $config
     *
     * @throws \LogicException
     */
    private function newMemcachedInstance($name, array $config, ContainerBuilder $container)
    {
        // Check if the Memcached extension is loaded
        if (!extension_loaded('memcached')) {
            throw LogicException('Memcached extension is not loaded! To configure memcached instances it MUST be loaded!');
        }

        $memcached = new Definition('Memcached');

        // Check if it has to be persistent
        if (isset($config['persistent_id'])) {
            $memcached->addArgument($config['persistent_id']);
        }

        // Add servers to the memcached instance
        $servers = array();
        foreach ($config['hosts'] as $host) {
            $servers[] = array(
                $host['dsn'],
                $host['port'],
                $host['weight']
            );
        }
        $memcached->addMethodCall('addServers', array($servers));

        // Add memcached options
        if (isset($config['memcached_options'])) {
            $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_COMPRESSION'), (bool) $config['memcached_options']['compression']));

            if ('php' != $config['memcached_options']['serializer']
                && false === constant('Memcached::HAVE_' . strtoupper($config['memcached_options']['serializer']))
            ) {
                throw new \LogicException('Invalid serializer specified for Memcached: ' . $config['memcached_options']['serializer']);
            }

            $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_SERIALIZER'), constant('Memcached::SERIALIZER_' . strtoupper($config['memcached_options']['serializer']))));
            $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_PREFIX_KEY'), $config['memcached_options']['prefix_key']));
            $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_HASH'), constant('Memcached::HASH_' . strtoupper($config['memcached_options']['hash']))));
            $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_DISTRIBUTION'), strtoupper('Memcached::DISTRIBUTION_' . $config['memcached_options']['distribution'])));

            if ('consistent' == $config['memcached_options']['distribution']) {
                $config['memcached_options']['libketama_compatible'] = true;
            }

            $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_LIBKETAMA_COMPATIBLE'), (bool) $config['memcached_options']['libketama_compatible']));
            $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_BUFFER_WRITES'), (bool) $config['memcached_options']['buffer_writes']));
            $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_BINARY_PROTOCOL'), (bool) $config['memcached_options']['binary_protocol']));
            $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_NO_BLOCK'), (bool) $config['memcached_options']['no_block']));
            $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_TCP_NODELAY'), (bool) $config['memcached_options']['tcp_nodelay']));
            $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_SOCKET_SEND_SIZE'), $config['memcached_options']['socket_send_size']));
            $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_SOCKET_RECV_SIZE'), $config['memcached_options']['socket_recv_size']));
            $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_CONNECT_TIMEOUT'), $config['memcached_options']['connect_timeout']));

            if ($config['memcached_options']['retry_timeout'] > 0) {
                $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_RETRY_TIMEOUT'), $config['memcached_options']['retry_timeout']));
            }

            $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_SEND_TIMEOUT'), $config['memcached_options']['send_timeout']));
            $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_RECV_TIMEOUT'), $config['memcached_options']['recv_timeout']));
            $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_POLL_TIMEOUT'), $config['memcached_options']['poll_timeout']));

            if (true === (bool) $config['memcached_options']['cache_lookups']) {
                $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_CACHE_LOOKUPS'), true));
            }

            if ($config['memcached_options']['server_failure_limit'] > 0) {
                $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_SERVER_FAILURE_LIMIT'), $config['memcached_options']['server_failure_limit']));
            }
        }

        $container->setDefinition(sprintf('emagister_memcached.memcached_instances.%s', $name), $memcached);
    }

    /**
     * Creates a new Memcache definition
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param string $name
     * @param array $config
     */
    private function newMemcacheInstance($name, array $config, ContainerBuilder $container)
    {
        // Check if the Memcache extension is loaded
        if (!extension_loaded('memcache')) {
            throw LogicException('Memcache extension is not loaded! To configure memcache instances it MUST be loaded!');
        }

        $memcache = new Definition('Memcache');
        foreach ((array) $config['hosts'] as $host) {
            $memcacheOptions = isset($host['memcache_options']) ? $host['memcache_options'] : array('persistent' => true, 'timeout' => 1, 'retry_interval' => 15, 'status' => true);
            $memcache->addMethodCall('addServer', array(
                $host['dsn'],
                $host['port'],
                $memcacheOptions['persistent'],
                $host['weight'] <= 0 ? 1 : $host['weight'],
                $memcacheOptions['timeout'],
                $memcacheOptions['retry_interval'],
                $memcacheOptions['status'],
            ));
        }

        $container->setDefinition(sprintf('emagister_memcached.memcache_instances.%s', $name), $memcache);
    }

    /**
     * Configures the Memcache instances
     *
     * @param array $options
     */
    private function configureMemcache(array $options)
    {
        foreach ($options as $name => $value) {
            ini_set(sprintf('memcache.%s', $name), $value);
        }
    }
}