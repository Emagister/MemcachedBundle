<?php

namespace Emagister\Bundle\MemcachedBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EnableSessionSupport implements CompilerPassInterface
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
        if (!$container->hasParameter('emagister_memcached.session.handler.type')) {
            return;
        }

        $type = $container->getParameter('emagister_memcached.session.handler.type');
        $container->setAlias('session.handler', sprintf('session.handler.%s', $type));
    }
}