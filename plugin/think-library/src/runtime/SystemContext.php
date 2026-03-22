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

namespace think\admin\runtime;

use think\admin\contract\SystemContextInterface;
use think\Container;

/**
 * 系统上下文静态入口。
 * 通过容器解析具体实现，避免 library 直接依赖 System 插件类名。
 * @class SystemContext
 * @method static getTokenHeader()
 * @method static getTokenCookie()
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
