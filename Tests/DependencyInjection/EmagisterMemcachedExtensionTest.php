<?php

namespace Emagister\Bundle\MemcachedBundle\Tests\DependencyInjection;

use Symfony\Component\Yaml\Parser;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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

    public function testLoad()
    {
        $config = $this->parseYaml($this->getYamlConfig());
        $this->object->load($config, $container = new ContainerBuilder());

        $this->assertTrue($container->hasParameter('emagister_memcached.configurations.default'));
        $this->assertInternalType('array', $container->getParameter('emagister_memcached.configurations.default'));
        $this->assertInternalType('array', $configs = $container->getParameter('emagister_memcached.configurations'));
        $this->assertEquals(array('default'), $configs);
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

    private function getYamlConfig()
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
}