<?php
return [
    'default' => [
        'host' => 'redis://'.config('app.redis.host').':6379',
        'options' => [
            'auth' => config('app.redis.password'),
            'db' => 0,
            'prefix' => '',
            'max_attempts'  => 5,
            'retry_seconds' => 5,
        ],
        // Connection pool, supports only Swoole or Swow drivers.
        'pool' => [
            'max_connections' => 5,
            'min_connections' => 1,
            'wait_timeout' => 3,
            'idle_timeout' => 60,
            'heartbeat_interval' => 50,
        ]
    ],
];
