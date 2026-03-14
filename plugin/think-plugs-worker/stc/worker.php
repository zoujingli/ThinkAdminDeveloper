<?php

declare(strict_types=1);

/**
 * ThinkPlugsWorker default configuration template.
 */
return [
    'defaults' => [
        'runtime' => [
            // 'stdout_file' => syspath('safefile/worker/shared.stdout.log'),
            // 'log_max_size' => 10 * 1024 * 1024,
            // 'stop_timeout' => 2,
            // 'event_loop' => \Workerman\Events\Event::class,
        ],
        'monitor' => [
            'files' => [
                'enabled' => true,
                'interval' => 3,
                'paths' => ['app', 'config', 'route', 'plugin'],
                'extensions' => ['php', 'env', 'ini', 'yaml', 'yml'],
            ],
            'memory' => [
                'enabled' => true,
                'interval' => 60,
                'limit' => '1G',
            ],
        ],
    ],
    'services' => [
        'http' => [
            'enabled' => true,
            'label' => 'ThinkAdmin HTTP',
            'driver' => 'http',
            'server' => [
                'host' => '127.0.0.1',
                'port' => 2346,
                'context' => [],
            ],
            'process' => [
                'name' => 'ThinkAdminHttp',
                'count' => 4,
            ],
        ],
        'queue' => [
            'enabled' => true,
            'label' => 'ThinkAdmin Queue',
            'driver' => 'queue',
            'process' => [
                'name' => 'ThinkAdminQueue',
                'count' => 2,
            ],
            'queue' => [
                'scan_interval' => 1,
                'batch_limit' => 20,
            ],
        ],
    ],
];
