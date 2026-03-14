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
