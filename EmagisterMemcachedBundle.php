<?php

namespace Emagister\Bundle\MemcachedBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Emagister\Bundle\MemcachedBundle\DependencyInjection\Compiler\CreateMemcachedInstancesPass;

class EmagisterMemcachedBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new CreateMemcachedInstancesPass());
    }
}