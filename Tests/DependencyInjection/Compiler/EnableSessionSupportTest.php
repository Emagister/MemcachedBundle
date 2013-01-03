<?php

namespace Emagister\Bundle\MemcachedBundle\Tests\DependencyInjection\Compiler;

use PHPUnit_Framework_TestCase;
use Mockery;
use Emagister\Bundle\MemcachedBundle\DependencyInjection\Compiler\EnableSessionSupport;

class EnableSessionSupportTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var EnableSessionSupport
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new EnableSessionSupport();
    }

    protected function tearDown()
    {
        $this->object = null;
    }

    public function testProcess()
    {
        $container = Mockery::mock('\Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->shouldReceive('hasParameter')->with('emagister_memcached.session.handler.type')->andReturn(true);
        $container->shouldReceive('getParameter')->with('emagister_memcached.session.handler.type')->andReturn('memcached');
        $container->shouldReceive('setAlias')->with('session.handler', 'session.handler.memcached');

        $this->assertNull($this->object->process($container));
    }
}