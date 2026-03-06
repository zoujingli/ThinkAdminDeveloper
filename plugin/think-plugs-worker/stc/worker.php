<?php

declare(strict_types=1);

/**
 * ThinkPlugsWorker default configuration.
 *
 * Targets:
 * - ThinkAdmin 8
 * - PHP 8.1+
 * - Workerman 5.1+
 */
return [
    // Http server listen host.
    'host' => '127.0.0.1',

    // Http server listen port.
    'port' => 2346,

    // Socket context options.
    'context' => [],

    // Custom server classes instantiated before Workerman::runAll().
    'classes' => '',

    // Optional message callback for the default http server.
    // Return true to stop default ThinkAdmin dispatch,
    // or return Workerman\Protocols\Http\Response directly.
    'callable' => null,

    // Workerman worker options.
    'worker' => [
        'name' => 'ThinkAdmin',
        'count' => 4,

        // Optional Workerman static options:
        // 'logFileMaxSize' => 10 * 1024 * 1024,
        // 'stopTimeout' => 2,
        // 'eventLoopClass' => \Workerman\Events\Event::class,
    ],

    // File change monitor.
    // Only effective in debug mode. On Windows it logs a restart hint,
    // on Linux/macOS it triggers a graceful reload.
    'files' => [
        'time' => 3,
        'path' => [],
        'exts' => ['php', 'env', 'ini', 'yaml', 'yml'],
    ],

    // Memory usage monitor.
    // When exceeded, workers are reloaded on POSIX platforms.
    'memory' => [
        'time' => 60,
        'limit' => '1G',
    ],

    // Custom servers.
    'customs' => [
        'websocket' => [
            'type' => 'Workerman',
            'listen' => 'websocket://0.0.0.0:8686',
            'context' => [],
            'classes' => '',
            'worker' => [
                'name' => 'ThinkAdminWebSocket',
                'count' => 1,
                'onMessage' => static function ($connection, $data): void {
                    $connection->send((string)$data);
                },
            ],
        ],
    ],
];
