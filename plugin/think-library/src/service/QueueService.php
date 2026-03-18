<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | 版权所有 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 开源协议 ( https://mit-license.org )
 * | 免责声明 ( https://thinkadmin.top/disclaimer )
 * | 会员特权 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
 * | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace think\admin\service;

use think\admin\contract\QueueManagerInterface;
use think\Container;

/**
 * Standard queue facade.
 * The concrete runtime implementation is provided by ThinkPlugsWorker.
 */
final class QueueService
{
    public const BIND_NAME = 'think.admin.runtime.queue';

    public const STATE_LOCK = 2;

    /**
     * QueueService constructor.
     */
    public static function instance(array $var = [], bool $new = false): QueueManagerInterface
    {
        return self::provider($var, $new);
    }

    /**
     * Register a new queue task.
     */
    public static function register(string $title, string $command, int $later = 0, array $data = [], int $loops = 0, ?int $legacyLoops = null): QueueManagerInterface
    {
        return self::provider([], true)->registerTask($title, $command, $later, $data, $loops, $legacyLoops);
    }

    /**
     * Get the current queue task code.
     */
    public static function currentCode(): string
    {
        return self::provider()->getCurrentCode();
    }

    /**
     * Check if the current request is in the queue context.
     */
    public static function inContext(?string $code = null): bool
    {
        return self::provider()->isInContext($code);
    }

    /**
     * Resolve the concrete queue provider.
     */
    private static function provider(array $vars = [], bool $newInstance = false): QueueManagerInterface
    {
        $container = Container::getInstance();
        if (!$container->bound(self::BIND_NAME)) {
            throw new \RuntimeException('ThinkPlugsWorker is required for queue runtime operations.');
        }

        $provider = $container->make($container->getAlias(self::BIND_NAME), $vars, $newInstance);
        if (!$provider instanceof QueueManagerInterface) {
            throw new \RuntimeException('Queue runtime provider must implement think\admin\contract\QueueManagerInterface.');
        }

        return $provider;
    }
}
