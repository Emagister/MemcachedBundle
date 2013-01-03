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
        // If no handler type specified or no is active session support, return
        if (!$container->hasParameter('emagister_memcached.session.handler.type')
            || !$container->hasAlias('session.storage')
        ) {
            return;
        }

        $type = $container->getParameter('emagister_memcached.session.handler.type');
        $container->setAlias('session.handler', sprintf('session.handler.%s', $type));
    }
}