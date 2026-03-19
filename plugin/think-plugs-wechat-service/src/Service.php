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

namespace plugin\wechat\service;

use plugin\wechat\service\command\Wechat;
use think\admin\Plugin;

/**
 * 应用插件注册服务
 * @class Service
 */
class Service extends Plugin
{
    protected string $appName = '微信开放平台';

    protected string $appCode = 'plugin-wechat-service';

    protected string $package = 'zoujingli/think-plugs-wechat-service';

    public function register(): void
    {
        $this->commands([Wechat::class]);
    }

    public static function menu(): array
    {
        $code = self::getAppCode();
        return [
            [
                'name' => '平台配置',
                'subs' => [
                    ['name' => '开放平台配置', 'icon' => 'layui-icon layui-icon-set', 'node' => "{$code}/config/index"],
                    ['name' => '公众号授权管理', 'icon' => 'layui-icon layui-icon-dialogue', 'node' => "{$code}/wechat/index"],
                ],
            ],
        ];
    }
}
