<?php

namespace Emagister\Bundle\MemcachedBundle\Tests\DependencyInjection;

use Symfony\Component\Yaml\Parser;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Emagister\Bundle\MemcachedBundle\DependencyInjection\EmagisterMemcachedExtension;
use PHPUnit_Framework_TestCase;
use Mockery;

class EmagisterMemcachedExtensionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var EmagisterMemcachedExtension
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new EmagisterMemcachedExtension();
    }

    protected function tearDown()
    {
        $this->object = null;
    }

    public function testLoadInstances()
    {
        $config = $this->parseYaml($this->getYamlConfigForInstances());
        $this->object->load($config, $container = new ContainerBuilder());

        $this->assertTrue($container->has('emagister_memcached.memcached_instances.ns1'));
        $this->assertTrue($container->has('emagister_memcached.memcache_instances.ns2'));
    }

    /**
     * @dataProvider loadSessionSupportDataProvider
     */
    public function testLoadSessionSupport($type, $method)
    {
        $config = $this->parseYaml($this->{$method}());
        $this->object->load($config, $container = new ContainerBuilder());

        $this->assertEquals($type, $container->getParameter('emagister_memcached.session.handler.type'));
    }

    public function testLoadMemcacheOptions()
    {
        $expected = array(
            'allow_failover' => true,
            'max_failover_attempts' => 20,
            'chunk_size' => 8192,
            'hash_strategy' => 'consistent',
            'hash_function' => 'crc32',
            'protocol' => 'binary',
            'redundancy' => 1,
            'session_redundancy' => 2,
            'compress_threshold' => 20000,
            'lock_timeout' => 15
        );

        $config = $this->parseYaml($this->getYamlConfigForMemcacheOptionsSupport());
        $this->object->load($config, $container = new ContainerBuilder());

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, ini_get(sprintf('memcache.%s', $key)));
        }
    }

    public function loadSessionSupportDataProvider()
    {
        return array(
            array('memcached', 'getYamlConfigForMemcachedSessionSupport'),
            array('memcache', 'getYamlConfigForMemcacheSessionSupport')
        );
    }

    /**
     * Parses a yaml string
     *
     * @param string $yaml
     *
     * @return array
     */
    private function parseYaml($yaml)
    {
        $parser = new Parser();
        return $parser->parse($yaml);
    }

    private function getYamlConfigForInstances()
    {
        return <<<EOY
emagister_memcached:
    instances:
        ns1:
            type: memcached
            persistent_id: ns1
            hosts:
                - { dsn: host1, port: 11211, weight: 15 }
                - { dsn: host2, port: 11211, weight: 30 }
            memcached_options:
                compression: true
                serializer: igbinary
                prefix_key: ns1
                hash: default
                distribution: consistent
                libketama_compatible: true
                buffer_writes: true
                binary_protocol: true
                no_block: true
                socket_send_size: 1
                socket_recv_size: 1
                connect_timeout: 1
                retry_timeout: 1
                send_timeout: 1
                recv_timeout: 1
                poll_timeout: 1
                cache_lookups: true
                server_failure_limit: 1
        ns2:
            type: memcache
            hosts:
                - { dsn: host1, port: 11211, weight: 15, memcache_options: { persistent: true, timeout: 1, retry_interval: 15, status: true } }
                - { dsn: host2, port: 11211, weight: 30, memcache_options: { persistent: true, timeout: 1, retry_interval: 15, status: true } }
EOY;
    }

    private function getYamlConfigForMemcachedSessionSupport()
    {
        return <<<EOY
emagister_memcached:
    session_support:
        enabled: true
        instance_id: ns1

    instances:
        ns1:
            type: memcached
            persistent_id: ns1
            hosts:
                - { dsn: host1, port: 11211, weight: 15 }
                - { dsn: host2, port: 11211, weight: 30 }
            memcached_options:
                compression: true
                serializer: igbinary
                prefix_key: ns1
                hash: default
                distribution: consistent
                libketama_compatible: true
                buffer_writes: true
                binary_protocol: true
                no_block: true
                socket_send_size: 1
                socket_recv_size: 1
                connect_timeout: 1
                retry_timeout: 1
                send_timeout: 1
                recv_timeout: 1
                poll_timeout: 1
                cache_lookups: true
                server_failure_limit: 1
        ns2:
            type: memcache
            hosts:
                - { dsn: host1, port: 11211, weight: 15, memcache_options: { persistent: true, timeout: 1, retry_interval: 15, status: true } }
                - { dsn: host2, port: 11211, weight: 30, memcache_options: { persistent: true, timeout: 1, retry_interval: 15, status: true } }
EOY;
    }

    private function getYamlConfigForMemcacheSessionSupport()
    {
        return <<<EOY
emagister_memcached:
    session_support:
        enabled: true
        instance_id: ns1

    instances:
        ns1:
            type: memcache
            hosts:
                - { dsn: host1, port: 11211, weight: 15, memcache_options: { persistent: true, timeout: 1, retry_interval: 15, status: true } }
                - { dsn: host2, port: 11211, weight: 30, memcache_options: { persistent: true, timeout: 1, retry_interval: 15, status: true } }
EOY;
    }

    private function getYamlConfigForMemcacheOptionsSupport()
    {
        return <<<EOY
emagister_memcached:
    memcache_options:
        allow_failover: true
        max_failover_attempts: 20
        chunk_size: 8192
        hash_strategy: consistent
        hash_function: crc32
        protocol: binary
        redundancy: 1
        session_redundancy: 2
        compress_threshold: 20000
        lock_timeout: 15
EOY;
    }
}