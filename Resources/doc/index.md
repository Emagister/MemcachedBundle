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
    session_support:
        enabled: true
        instance_id: instance1
        options:
            prefix: "my_session_prefix_"
            expiretime: 172800

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
```

To reference those instances in your code or in other configuration files you will have to
use the instance name:

```php
<?php

$memcached = $this->get('emagister_memcached.memcached_instances.instance1');
$memcache = $this->get('emagister_memcached.memcached_instances.instance2');
```

### Memcached / Memcache configuration ###

If you want to see all the Memcached / Memcache configuration options you can check it out on the
extension documentation in the PHP site

#### Memcached ####

* http://php.net/manual/en/memcached.constants.php (Memcached configuration reference per instance)

#### Memcache ####

* http://php.net/manual/en/memcache.ini.php (Memcache configuration reference)
* http://php.net/manual/en/memcache.addserver.php (Memcache connection options)

### Session Support ###

This bundle also provides support for storing session data on Memcache servers. To enable session support
you will have to enable it through the ```session_support``` key. Note that the only required subkeys of
the session support are: ```enabled``` (defaults to ```false```) and ```instance_id``` (a valid instance).
You can also specify a key prefix and an expiretime.

```yml
emagister_memcached:
    session_support:
        enabled: true
        instance_id: instance1
        options:
            prefix: "my_session_prefix_"
            expiretime: 172800

    # Instances configuration
```