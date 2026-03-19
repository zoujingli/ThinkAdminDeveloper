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
     */
    protected string $appName = '应用中心';

    /**
     * 定义插件入口.
     */
    protected string $appCode = 'plugin-center';

    /**
     * 定义安装包名.
     */
    protected string $package = 'zoujingli/think-plugs-center';

    /**
     * 定义插件菜单.
     */
    public static function menu(): array
    {
        return [];
    }
}
