# Emagister MemcachedBundle #

## About ##

This bundle helps configuring ```Memcached``` and ```Memcache``` instances eficiently into
a Symfony2 application.

## Installation ##

Add the ```emagister/memcached-bundle``` package to your require section in the composer.json
file.

```json
{
    "require": {
        "emagister/memcached-bundle": "dev-master"
    }
}
```

## Usage ##

Below you can see a full configuration for this bundle.

```yml
emagister_memcached:
    instances:
        instance1:
            type: memcached
            persistent_id: instance1
            hosts:
                - { dsn: host1, port: 11211, weight: 15 }
                - { dsn: host2, port: 11211, weight: 30 }
            memcached_options:
                compression: true
                serializer: igbinary
                prefix_key: instance1
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
        instance2:
            type: memcache
            hosts:
                - { dsn: host1, port: 11211, weight: 15, memcache_options: { persistent: true, timeout: 1, retry_interval: 15, status: true } }
                - { dsn: host2, port: 11211, weight: 30, memcache_options: { persistent: true, timeout: 1, retry_interval: 15, status: true } }
```

To reference those instances in your code or in other configuration files you will have to
use the instance name:

```php
<?php

$memcached = $this->get('emagister_memcached.memcached_instances.instance1');
$memcache = $this->get('emagister_memcached.memcached_instances.instance2');
```