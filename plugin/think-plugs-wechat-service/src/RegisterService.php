<?php

// +----------------------------------------------------------------------
// | Wechat Service Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 Anyon <zoujingli@qq.com>
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wechat-service
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wechat-service
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wechat\service;

use plugin\wechat\service\command\Wechat;
use think\admin\Plugin;

/**
 * 应用插件注册服务
 * @class RegisterService
 * @package plugin\wechat\service
 */
class RegisterService extends Plugin
{

    protected $appName = '微信开放平台';

    protected $appCode = 'plugin-wechat-service';

    protected $package = 'zoujingli/think-plugs-wechat-service';

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
                    ['name' => '授权微信管理', 'icon' => "layui-icon layui-icon-dialogue", 'node' => "{$code}/wechat/index"],
                ]
            ]
        ];
    }
}