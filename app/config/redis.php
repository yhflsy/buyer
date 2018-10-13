<?php

return array(
    'default' => array(
        'redis_host' => '172.17.9.29',
        'redis_port' => 6379,
        'redis_auth' => '',
        'connect_timeout' => 1,
    ),

    'test' => array(
        'redis_host' => '172.17.9.29',
        'redis_port' => 6379,
        'redis_auth' => '',
        'connect_timeout' => 1,
    ),

    'pre' => array(
        'redis_host' => '10.0.1.177',
        'redis_port' => 6379,
        'connect_timeout' => 1,
        'redis_auth' => 'tripb2b',
    ),

    'product' => array(
        'redis_host' => '10.0.0.23',
        'redis_port' => 6379,
        'connect_timeout' => 0.3,
        'redis_auth' => '',
    ),
);