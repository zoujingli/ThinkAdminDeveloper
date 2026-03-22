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

namespace plugin\wechat\client;

use plugin\wechat\client\command\Auto;
use plugin\wechat\client\command\Clear;
use plugin\wechat\client\command\Fans;
use plugin\wechat\client\service\AutoService;
use plugin\wechat\client\service\PaymentService;
use think\admin\extend\CodeToolkit;
use think\admin\Plugin;
use think\Request;

/**
 * 组件注册服务
 * @class Service
 */
class Service extends Plugin
{
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
        $notify = static function (Request $request) {
            try {
                $data = json_decode(CodeToolkit::deSafe64($request->param('vars')), true);
                return PaymentService::notify($data);
            } catch (\Error|\Exception $exception) {
                return "Error: {$exception->getMessage()}";
            }
        };
        $this->app->route->any('/api/wechat-client/payment/notify/:vars', $notify)->name('wechat-client-payment-notify');
        $this->app->route->any('/plugin-wxpay-notify/:vars', $notify)->name('plugin-wxpay-notify');
    }
}
