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

namespace plugin\payment;

use plugin\payment\service\Payment;
use think\admin\extend\CodeToolkit;
use think\admin\Plugin;
use think\Request;

/**
 * 插件注册服务
 * @class Service
 */
class Service extends Plugin
{
    /**
     * 插件服务注册。
     */
    public function register(): void
    {
        // 注册支付通知路由
        $this->app->route->any('/plugin-payment-notify/:vars', function (Request $request) {
            try {
                $data = json_decode(CodeToolkit::deSafe64($request->param('vars')), true);
                return Payment::mk($data['channel'])->notify($data);
            } catch (\Error|\Exception $exception) {
                return 'Error: ' . $exception->getMessage();
            }
        });
    }
}
