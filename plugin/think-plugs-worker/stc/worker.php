<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdminDeveloper
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed: https://mit-license.org
 * | Disclaimer: https://thinkadmin.top/disclaimer
 * | Vip Rights: https://thinkadmin.top/vip-introduce
 * +----------------------------------------------------------------------
 * | Gitee Repository: https://gitee.com/zoujingli/ThinkAdmin
 * | Github Repository: https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
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
