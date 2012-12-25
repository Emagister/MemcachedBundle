<?php

namespace Emagister\Bundle\MemcachedBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class CreateMemcachedInstancesPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter('emagister_memcached.configurations')) {
            return;
        }

        $configurations = $container->getParameter('emagister_memcached.configurations');

        foreach ($configurations as $configuration) {
            $instances = $container->getParameter(sprintf('emagister_memcached.configurations.%s', $configuration));

            foreach ($instances as $instance => $memcachedConfig) {
                $method = 'new' . ucfirst($memcachedConfig['type']) . 'Instance';

                if (!method_exists($this, $method)) {
                    throw new \LogicException(sprintf('"%s" not supported', $memcachedConfig['type']));
                }

                $this->{$method}($container, $instance, $memcachedConfig);
            }
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
    private function newMemcachedInstance(ContainerBuilder $container, $name, array $config)
    {
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
            $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_RETRY_TIMEOUT'), $config['memcached_options']['retry_timeout']));
            $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_SEND_TIMEOUT'), $config['memcached_options']['send_timeout']));
            $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_RECV_TIMEOUT'), $config['memcached_options']['recv_timeout']));
            $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_POLL_TIMEOUT'), $config['memcached_options']['poll_timeout']));
            $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_CACHE_LOOKUPS'), (bool) $config['memcached_options']['cache_lookups']));
            $memcached->addMethodCall('setOption', array(constant('Memcached::OPT_SERVER_LIMIT_FAILURE'), $config['memcached_options']['server_failure_limit']));
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
    private function newMemcacheInstance(ContainerBuilder $container, $name, array $config)
    {
        $memcache = new Definition('Memcache');
        foreach ((array) $config['hosts'] as $host) {
            $memcacheOptions = isset($host['memcache_options']) ? $host['memcache_options'] : array('persistent' => true, 'timeout' => 1, 'retry_interval' => 15, 'status' => true);
            $memcache->addMethodCall('addServer', array(
                $host['dsn'],
                $host['port'],
                $memcacheOptions['persistent'],
                $host['weight'],
                $memcacheOptions['timeout'],
                $memcacheOptions['retry_interval'],
                $memcacheOptions['status'],
            ));
        }

        $container->setDefinition(sprintf('emagister_memcached.memcache_instances.%s', $name), $memcache);
    }
}