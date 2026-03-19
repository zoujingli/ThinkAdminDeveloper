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

namespace plugin\account;

use think\admin\Plugin;

/**
 * 插件注册服务
 * @class Service
 */
class Service extends Plugin
{
    /**
     * 定义插件名称.
     */
    protected string $appName = '账号管理';

    /**
     * 定义安装包名.
     */
    protected string $package = 'zoujingli/think-plugs-account';

    /**
     * 定义插件菜单.
     * @return array[]
     */
    public static function menu(): array
    {
        $code = static::getAppCode();
        return [
            [
                'name' => '用户管理',
                'subs' => [
                    ['name' => '用户账号管理', 'icon' => 'layui-icon layui-icon-user', 'node' => "{$code}/master/index"],
                    ['name' => '终端账号管理', 'icon' => 'layui-icon layui-icon-cellphone', 'node' => "{$code}/device/index"],
                    ['name' => '手机短信管理', 'icon' => 'layui-icon layui-icon-email', 'node' => "{$code}/message/index"],
                ],
            ],
        ];
    }
}
