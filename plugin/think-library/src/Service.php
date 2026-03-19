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
 * 自定义服务基类.
 * @class Service
 */
abstract class Service
{
    /**
     * 应用实例.
     */
    protected App $app;

    /**
     * Constructor.
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->initialize();
    }

    /**
     * 静态实例对象
     * @param array $var 实例参数
     * @param bool $new 创建新实例
     */
    public static function instance(array $var = [], bool $new = false): static
    {
        return Container::getInstance()->make(static::class, $var, $new);
    }

    /**
     * 初始化服务
     */
    protected function initialize() {}
}
