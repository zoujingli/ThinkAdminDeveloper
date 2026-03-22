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

namespace think\admin;

use think\App;
use think\Container;

/**
 * 控制器助手基类.
 *
 * 为控制器提供通用的辅助功能，包括表单构建、查询构建、页面构建等
 * 所有 Helper 类都继承自此类
 *
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
     * Helper 构造函数.
     *
     * @param App $app 应用实例
     * @param Controller $class 控制器实例
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
     * 实例化 Helper 对象（支持依赖注入）.
     *
     * @param mixed ...$args 构造函数参数
     */
    public static function instance(...$args): static
    {
        return Container::getInstance()->invokeClass(static::class, $args);
    }
}
