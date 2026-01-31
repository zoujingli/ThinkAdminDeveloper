<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | Payment Plugin for ThinkAdmin
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

namespace plugin\center;

use think\admin\Plugin;

/**
 * 组件注册服务
 * @class Service
 */
class Service extends Plugin
{
    /**
     * 定义插件名称.
     * @var string
     */
    protected $appName = '应用中心';

    /**
     * 定义插件入口.
     * @var string
     */
    protected $appCode = 'plugin-center';

    /**
     * 定义安装包名.
     * @var string
     */
    protected $package = 'zoujingli/think-plugs-center';

    /**
     * 定义插件菜单.
     */
    public static function menu(): array
    {
        return [];
    }
}
