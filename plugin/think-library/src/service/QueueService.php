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
class QueueService
{
    public const BIND_NAME = 'think.admin.runtime.queue';

    public static function instance(array $var = [], bool $new = false): QueueManagerInterface
    {
        return static::provider($var, $new);
    }

    public static function register(string $title, string $command, int $later = 0, array $data = [], int $rscript = 0, int $loops = 0): QueueManagerInterface
    {
        return static::provider([], true)->registerTask($title, $command, $later, $data, $rscript, $loops);
    }

    public static function currentCode(): string
    {
        return static::provider()->getCurrentCode();
    }

    public static function inContext(?string $code = null): bool
    {
        return static::provider()->isInContext($code);
    }

    /**
     * Resolve the concrete queue provider.
     */
    protected static function provider(array $vars = [], bool $newInstance = false): QueueManagerInterface
    {
        $container = Container::getInstance();
        if (!$container->bound(static::BIND_NAME)) {
            throw new \RuntimeException('ThinkPlugsWorker is required for queue runtime operations.');
        }

        $provider = $container->make($container->getAlias(static::BIND_NAME), $vars, $newInstance);
        if (!$provider instanceof QueueManagerInterface) {
            throw new \RuntimeException('Queue runtime provider must implement think\admin\contract\QueueManagerInterface.');
        }

        return $provider;
    }
}
