<?php

namespace Emagister\Bundle\MemcachedBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('emagister_memcached');

        $rootNode
            ->append($this->addSessionSupportSection())
            ->append($this->addInstancesSection())
            ->append($this->addMemcacheOptionsSection())
        ;

        return $treeBuilder;
    }

    /**
     * Configure the "instances" section
     *
     * @return ArrayNodeDefinition
     */
    private function addInstancesSection()
    {
        $tree = new TreeBuilder();
        $node = $tree->root('instances');

        $node
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->scalarNode('type')
                        ->cannotBeEmpty()
                        ->isRequired()
                        ->defaultValue('memcached')
                        ->validate()
                        ->ifNotInArray(array('memcache', 'memcached'))
                            ->thenInvalid('The type must be either memcache or memcached!')
                        ->end()
                    ->end()
                    ->scalarNode('persistent_id')
                        ->defaultNull()
                        ->info('This option only applies to Memcached instances')
                    ->end()
                    ->arrayNode('hosts')
                        ->requiresAtLeastOneElement()
                        ->prototype('array')
                            ->children()
                                ->scalarNode('dsn')->cannotBeEmpty()->isRequired()->end()
                                ->scalarNode('port')
                                    ->cannotBeEmpty()
                                    ->defaultValue(11211)
                                    ->validate()
                                    ->ifTrue(function ($v) { return !is_numeric($v); })
                                        ->thenInvalid('Memcached port must be a valid integer!')
                                    ->end()
                                ->end()
                                ->scalarNode('weight')
                                    ->defaultValue(0)
                                    ->validate()
                                    ->ifTrue(function ($v) { return !is_numeric($v); })
                                        ->thenInvalid('Memcached weight must be a valid integer!')
                                    ->end()
                                ->end()
                                ->arrayNode('memcache_options')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->booleanNode('persistent')->defaultTrue()->end()
                                        ->scalarNode('timeout')
                                            ->defaultValue(1)
                                            ->validate()
                                            ->ifTrue(function ($v) { return !is_numeric($v); })
                                                ->thenInvalid('Memcache timeout must be a valid integer!')
                                            ->end()
                                        ->end()
                                        ->scalarNode('retry_interval')
                                            ->defaultValue(15)
                                            ->validate()
                                            ->ifTrue(function ($v) { return !is_numeric($v); })
                                                ->thenInvalid('Memcache retry interval must be a valid integer!')
                                            ->end()
                                        ->end()
                                        ->booleanNode('status')->defaultTrue()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->append($this->addMemcachedOptionsSection())
                ->end()
            ->end()
        ->end();

        return $node;
    }

    /**
     * Configure the "session_support" section
     *
     * @return ArrayNodeDefinition
     */
    private function addSessionSupportSection()
    {
        $tree = new TreeBuilder();
        $node = $tree->root('session_support');

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('enabled')->defaultFalse()->end()
                ->scalarNode('instance_id')->end()
                ->arrayNode('options')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('prefix')
                            ->defaultValue('sf2')
                            ->isRequired()
                        ->end()
                        ->scalarNode('expiretime')
                            ->defaultValue('86400')
                            ->isRequired()
                            ->validate()
                            ->ifTrue(function ($v) { return !is_int($v); })
                                ->thenInvalid('The expiretime parameter must be an integer!')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $node;
    }

    /**
     * Configure the "memcache_options" section
     *
     * @return ArrayNodeDefinition
     */
    private function addMemcacheOptionsSection()
    {
        $tree = new TreeBuilder();
        $node = $tree->root('memcache_options');

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('allow_failover')->defaultTrue()->end()
                ->scalarNode('max_failover_attempts')
                    ->defaultValue(20)
                    ->validate()
                    ->ifTrue(function ($v) { return !is_numeric($v); })
                        ->thenInvalid('The max failover attempts for Memcache should be a number')
                    ->end()
                ->end()
                ->scalarNode('chunk_size')
                    ->defaultValue(8192)
                    ->validate()
                    ->ifTrue(function ($v) { return !is_numeric($v); })
                        ->thenInvalid('The chunk size for Memcache should be a number')
                    ->end()
                ->end()
                ->scalarNode('hash_strategy')
                    ->defaultValue('standard')
                    ->validate()
                    ->ifNotInArray(array('standard', 'consistent'))
                        ->thenInvalid('The hash strategy should be either standard or consistent')
                    ->end()
                ->end()
                ->scalarNode('hash_function')
                    ->defaultValue('crc32')
                    ->validate()
                    ->ifNotInArray(array('crc32', 'fnv'))
                        ->thenInvalid('The hash function should be either crc32 or fnv')
                    ->end()
                ->end()
                ->scalarNode('protocol')
                    ->defaultValue('ascii')
                    ->validate()
                    ->ifNotInArray(array('ascii', 'binary'))
                        ->thenInvalid('The protocol should be either ascii or binary')
                    ->end()
                ->end()
                ->scalarNode('redundancy')
                    ->defaultValue(1)
                    ->validate()
                    ->ifTrue(function ($v) { return !is_numeric($v); })
                        ->thenInvalid('The redundancy parameter for Memcache should be a number')
                    ->end()
                ->end()
                ->scalarNode('session_redundancy')
                    ->defaultValue(2)
                    ->validate()
                    ->ifTrue(function ($v) { return !is_numeric($v); })
                        ->thenInvalid('The session redundancy parameter for Memcache should be a number')
                    ->end()
                ->end()
                ->scalarNode('compress_threshold')
                    ->defaultValue(20000)
                    ->validate()
                    ->ifTrue(function ($v) { return !is_numeric($v); })
                        ->thenInvalid('The compress threshold parameter for Memcache should be a number')
                    ->end()
                ->end()
                ->scalarNode('lock_timeout')
                    ->defaultValue(15)
                    ->validate()
                    ->ifTrue(function ($v) { return !is_numeric($v); })
                        ->thenInvalid('The lock timeout parameter for Memcache should be a number')
                    ->end()
                ->end()
            ->end()
        ->end()
        ;

        return $node;
    }

    /**
     * Configure the "memcached_options" section
     *
     * @return ArrayNodeDefinition
     */
    private function addMemcachedOptionsSection()
    {
        $tree = new TreeBuilder();
        $node = $tree->root('memcached_options');

        // Memcached only configs
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('compression')->defaultTrue()->end()
                ->scalarNode('serializer')
                    ->defaultValue('php')
                    ->validate()
                    ->ifNotInArray(array('php', 'json', 'igbinary'))
                        ->thenInvalid('Invalid value for serializer')
                    ->end()
                ->end()
                ->scalarNode('prefix_key')->defaultValue('')->end()
                ->scalarNode('hash')
                    ->defaultValue('default')
                    ->validate()
                    ->ifNotInArray(array('default', 'md5', 'crc', 'fnv1_64', 'fnv1a_64', 'fnv1_32', 'fnv1a_32', 'hsieh', 'murmur'))
                        ->thenInvalid('Invalid value for hash!')
                    ->end()
                ->end()
                ->scalarNode('distribution')
                    ->defaultValue('modula')
                    ->validate()
                    ->ifNotInArray(array('modula', 'consistent'))
                        ->thenInvalid('Must be either modula or consistent')
                    ->end()
                ->end()
                ->booleanNode('libketama_compatible')
                    ->info('It is highly recommended to enable this option if you want to use consistent hashing, and it may be enabled by default in future releases.')
                    ->defaultFalse()
                ->end()
                ->booleanNode('buffer_writes')->defaultFalse()->end()
                ->booleanNode('binary_protocol')->defaultFalse()->end()
                ->booleanNode('no_block')->defaultFalse()->end()
                ->booleanNode('tcp_nodelay')->defaultFalse()->end()
                ->scalarNode('socket_send_size')
                    ->defaultNull()
                    ->validate()
                    ->ifTrue(function($v) { return !is_numeric($v); })
                        ->thenInvalid('Must be number!')
                    ->end()
                ->end()
                ->scalarNode('socket_recv_size')
                    ->defaultNull()
                    ->validate()
                    ->ifTrue(function($v) { return !is_numeric($v); })
                        ->thenInvalid('Must be number!')
                    ->end()
                ->end()
                ->scalarNode('connect_timeout')
                    ->defaultValue(1000)
                    ->validate()
                    ->ifTrue(function($v) { return !is_numeric($v); })
                        ->thenInvalid('Must be number!')
                    ->end()
                ->end()
                ->scalarNode('retry_timeout')
                    ->defaultValue(0)
                    ->validate()
                    ->ifTrue(function($v) { return !is_numeric($v); })
                        ->thenInvalid('Must be number!')
                    ->end()
                ->end()
                ->scalarNode('send_timeout')
                    ->defaultValue(0)
                    ->validate()
                    ->ifTrue(function($v) { return !is_numeric($v); })
                        ->thenInvalid('Must be number!')
                    ->end()
                ->end()
                ->scalarNode('recv_timeout')
                    ->defaultValue(0)
                    ->validate()
                    ->ifTrue(function($v) { return !is_numeric($v); })
                        ->thenInvalid('Must be number!')
                    ->end()
                ->end()
                ->scalarNode('poll_timeout')
                    ->defaultValue(1000)
                    ->validate()
                    ->ifTrue(function($v) { return !is_numeric($v); })
                        ->thenInvalid('Must be number!')
                    ->end()
                ->end()
                ->booleanNode('cache_lookups')->defaultFalse()->end()
                ->scalarNode('server_failure_limit')
                    ->defaultValue(0)
                    ->validate()
                    ->ifTrue(function($v) { return !is_numeric($v); })
                        ->thenInvalid('Must be number!')
                    ->end()
                ->end()
            ->end()
        ->end();

        return $node;
    }
}