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

namespace think\admin\helper;

use think\admin\Controller;
use think\App;
use think\Container;

/**
 * 控制器助手.
 * @class Helper
 */
abstract class Helper
{
    /**
     * 应用容器.
     */
    public App $app;

    /**
     * 控制器实例.
     */
    public Controller $class;

    /**
     * 当前请求方式.
     */
    public string $method;

    /**
     * 自定输出格式.
     */
    public string $output;

    /**
     * Helper constructor.
     */
    public function __construct(App $app, Controller $class)
    {
        $this->app = $app;
        $this->class = $class;
        // 计算指定输出格式
        $output = strval($app->request->request('output', 'default'));
        $method = $app->request->method() ?: ($app->runningInConsole() ? 'cli' : 'nil');
        $this->method = strtolower($method);
        $this->output = "{$this->method}." . strtolower($output);
    }

    /**
     * 实例对象反射.
     * @param array $args
     */
    public static function instance(...$args): static
    {
        return Container::getInstance()->invokeClass(static::class, $args);
    }
}
