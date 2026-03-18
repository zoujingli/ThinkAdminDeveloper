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

namespace think\admin\runtime;

use think\admin\contract\SystemContextInterface;
use think\Container;

/**
 * 系统上下文静态入口。
 * 通过容器解析具体实现，避免 library 直接依赖 System 插件类名。
 * @class SystemContext
 */
class SystemContext
{
    /**
     * 代理上下文调用.
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
    {
        return static::instance()->{$name}(...$arguments);
    }

    /**
     * 获取系统上下文实例.
     */
    public static function instance(): SystemContextInterface
    {
        $container = Container::getInstance();
        if (!$container->bound(SystemContextInterface::class)) {
            $container->bind(SystemContextInterface::class, NullSystemContext::class);
        }
        return $container->make(SystemContextInterface::class);
    }
}
