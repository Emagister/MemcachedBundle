<?php

namespace Emagister\Bundle\MemcachedBundle\Tests\DependencyInjection\Compiler;

use Emagister\Bundle\MemcachedBundle\DependencyInjection\Compiler\CreateMemcachedInstancesPass;

use PHPUnit_Framework_TestCase;
use Mockery;

class CreateMemcachedInstancesPassTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var CreateMemcachedInstancesPass
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new CreateMemcachedInstancesPass();
    }

    protected function tearDown()
    {
        $this->object = null;
    }

    public function testProcessDoesNothingWhenNoConfigurationsAvailable()
    {
        $container = Mockery::mock('\Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->shouldReceive('hasParameter')->with('emagister_memcached.configurations')->andReturn(false);

        $this->assertNull($this->object->process($container));
    }

    /**
     * data provider for testProcess
     *
     * @return array
     */
    public function configurations()
    {
        return array(
            array(
                array(
                    'ns1' => array(
                        'type' => 'memcached',
                        'hosts' => array(
                            array(
                                'dsn' => 'host1',
                                'port' => 11211,
                                'weight' => 1
                            )
                        )
                    )
                )
            ),
            array(
                array(
                    'ns2' => array(
                        'type' => 'memcache',
                        'hosts' => array(
                            array(
                                'dsn' => 'host2',
                                'port' => 11211,
                                'weight' => 1
                            )
                        )
                    )
                )
            )
        );
    }

    /**
     * @dataProvider configurations
     */
    public function testProcessMemcachedConfigurations($config)
    {
        $ns = key($config);
        $type = $config[$ns]['type'];

        $container = Mockery::mock('\Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->shouldReceive('hasParameter')->with('emagister_memcached.configurations')->andReturn(true);
        $container->shouldReceive('getParameter')->with('emagister_memcached.configurations')->andReturn(array($ns));
        $container->shouldReceive('getParameter')->with(sprintf('emagister_memcached.configurations.%s', $ns))->andReturn($config);
        $container->shouldReceive('setDefinition')->with(sprintf('emagister_memcached.%s_instances.%s', $type, $ns), Mockery::type('\Symfony\Component\DependencyInjection\Definition'));

        $this->assertNull($this->object->process($container));
    }
}