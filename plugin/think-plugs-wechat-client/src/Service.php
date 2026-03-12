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

namespace plugin\wechat\client;

use plugin\wechat\client\command\Auto;
use plugin\wechat\client\command\Clear;
use plugin\wechat\client\command\Fans;
use plugin\wechat\client\service\AutoService;
use plugin\wechat\client\service\PaymentService;
use think\admin\extend\codec\CodeToolkit;
use think\admin\Plugin;
use think\Request;

/**
 * 组件注册服务
 * @class Service
 */
class Service extends Plugin
{
    /**
     * 定义插件入口.
     * @var string
     */
    protected string $appCode = 'wechat';

    /**
     * 定义插件访问前缀.
     * @var string
     */
    protected string $appPrefix = 'wechat';

    /**
     * 定义插件名称.
     * @var string
     */
    protected string $appName = '微信公众平台';

    /**
     * 定义安装包名.
     * @var string
     */
    protected string $package = 'zoujingli/think-plugs-wechat-client';

    /**
     * 注册组件服务
     */
    public function register(): void
    {
        // 注册模块指令
        $this->commands([Fans::class, Auto::class, Clear::class]);

        // 注册粉丝关注事件
        $this->app->event->listen('WechatFansSubscribe', static function ($openid) {
            AutoService::register($openid);
        });

        // 注册支付通知路由
        $this->app->route->any('/plugin-wxpay-notify/:vars', static function (Request $request) {
            try {
                $data = json_decode(CodeToolkit::deSafe64($request->param('vars')), true);
                return PaymentService::notify($data);
            } catch (\Error|\Exception $exception) {
                return "Error: {$exception->getMessage()}";
            }
        });
    }

    /**
     * 增加微信配置.
     * @return array[]
     */
    public static function menu(): array
    {
        $code = static::getAppCode();
        // 设置插件菜单
        return [
            [
                'name' => '公众号平台',
                'subs' => [
                    ['name' => '微信接口配置', 'icon' => 'layui-icon layui-icon-set', 'node' => "{$code}/config/options"],
                    ['name' => '微信支付配置', 'icon' => 'layui-icon layui-icon-rmb', 'node' => "{$code}/config/payment"],
                ],
            ],
            [
                'name' => '微信定制',
                'subs' => [
                    ['name' => '微信粉丝管理', 'icon' => 'layui-icon layui-icon-username', 'node' => "{$code}/fans/index"],
                    ['name' => '微信图文管理', 'icon' => 'layui-icon layui-icon-template-1', 'node' => "{$code}/news/index"],
                    ['name' => '微信菜单配置', 'icon' => 'layui-icon layui-icon-cellphone', 'node' => "{$code}/menu/index"],
                    ['name' => '回复规则管理', 'icon' => 'layui-icon layui-icon-engine', 'node' => "{$code}/keys/index"],
                    ['name' => '关注自动回复', 'icon' => 'layui-icon layui-icon-release', 'node' => "{$code}/auto/index"],
                ],
            ],
            [
                'name' => '微信支付',
                'subs' => [
                    ['name' => '微信支付行为', 'icon' => 'layui-icon layui-icon-rmb', 'node' => "{$code}/payment.record/index"],
                    ['name' => '微信退款管理', 'icon' => 'layui-icon layui-icon-engine', 'node' => "{$code}/payment.refund/index"],
                ],
            ],
        ];
    }
}
