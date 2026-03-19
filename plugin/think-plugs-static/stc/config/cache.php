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
    // 默认缓存驱动
    'default' => env('CACHE_TYPE', 'file'),
    // 缓存连接配置
    'stores' => [
        'file' => [
            // 驱动方式
            'type' => 'File',
            // 缓存保存目录
            'path' => '',
            // 缓存名称前缀
            'prefix' => '',
            // 缓存有效期 0 表示永久缓存
            'expire' => 0,
            // 缓存标签前缀
            'tag_prefix' => 'tag:',
            // 序列化机制
            'serialize' => [],
        ],
        'safe' => [
            // 驱动方式
            'type' => 'File',
            // 缓存保存目录
            'path' => runpath('safefile/cache/'),
            // 缓存名称前缀
            'prefix' => '',
            // 缓存有效期 0 表示永久缓存
            'expire' => 0,
            // 缓存标签前缀
            'tag_prefix' => 'tag:',
            // 序列化机制
            'serialize' => [],
        ],
        'redis' => [
            // 驱动方式
            'type' => 'redis',
            'host' => env('CACHE_REDIS_HOST', '127.0.0.1'),
            'port' => env('CACHE_REDIS_PORT', 6379),
            'select' => env('CACHE_REDIS_SELECT', 0),
            'password' => env('CACHE_REDIS_PASSWORD', ''),
        ],
    ],
];
