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

namespace plugin\wemall;

use plugin\account\model\PluginAccountUser;
use plugin\payment\model\PluginPaymentRecord;
use plugin\wemall\command\Clear;
use plugin\wemall\command\Trans;
use plugin\wemall\command\Users;
use plugin\wemall\model\PluginWemallOrder;
use plugin\wemall\model\PluginWemallUserRelation;
use plugin\wemall\service\UserOrder;
use plugin\wemall\service\UserRebate;
use plugin\wemall\service\UserUpgrade;
use think\admin\Plugin;
use think\exception\HttpResponseException;
use think\Request;

/**
 * 插件服务注册.
 * @class Service
 */
class Service extends Plugin
{
    /**
     * 插件服务注册.
     */
    public function register(): void
    {
        $this->commands([Clear::class, Trans::class, Users::class]);

        // 注册时填写推荐时检查
        $this->app->middleware->add(function (Request $request, \Closure $next) {
            $input = $request->post(['from', 'phone', 'fphone']);
            if (!empty($input['phone']) && !empty($input['fphone'])) {
                $showError = static function ($message, array $data = []) {
                    throw new HttpResponseException(json(['code' => 0, 'info' => lang($message), 'data' => $data]));
                };
                $where = [];
                if (preg_match('/^1\d{10}$/', $input['fphone'])) {
                    $where['phone'] = $input['fphone'];
                } else {
                    if (empty($input['from'])) {
                        $showError('无效推荐人');
                    }
                    $where['id'] = $input['from'];
                }
                // 判断推荐人是否可
                $from = PluginAccountUser::mk()->where($where)->findOrEmpty();
                if ($from->isEmpty()) {
                    $showError('无效邀请人！');
                }
                if ($from->getAttr('phone') == $input['phone']) {
                    $showError('不能邀请自己！');
                }
                [$rela] = PluginWemallUserRelation::withRelation($from->getAttr('id'));
                if (empty($rela['entry_agent'])) {
                    $showError('无邀请权限！');
                }
                // 检查自己是否已绑定
                $where = ['phone' => $input['phone']];
                if (($user = PluginAccountUser::mk()->where($where)->findOrEmpty())->isExists()) {
                    [$rela] = PluginWemallUserRelation::withRelation($user->getAttr('id'));
                    if (!empty($rela['puid1']) && $rela['puid1'] != $from->getAttr('id')) {
                        $showError('该用户已注册');
                    }
                }
            }
            return $next($request);
        }, 'route');

        // 注册用户绑定事件
        $this->app->event->listen('PluginAccountBind', function (array $data) {
            $this->app->log->notice("Event PluginAccountBind {$data['unid']}#{$data['usid']}");
            // 初始化用户关系数据
            PluginWemallUserRelation::withInit(intval($data['unid']));
            // 尝试临时绑定推荐人用户
            $input = $this->app->request->post(['from', 'phone', 'fphone']);
            if (!empty($input['fphone'])) {
                try {
                    $map = [];
                    if (preg_match('/^1\d{10}$/', $input['fphone'])) {
                        $map['phone'] = $input['fphone'];
                    } else {
                        $map['id'] = $input['from'] ?? 0;
                    }
                    $from = PluginAccountUser::mk()->where($map)->value('id');
                    if ($from > 0) {
                        UserUpgrade::bindAgent(intval($data['unid']), $from, 0);
                    }
                } catch (\Exception $exception) {
                    trace_file($exception);
                }
            }
        });

        // 注册支付审核事件
        $this->app->event->listen('PluginPaymentAudit', function (PluginPaymentRecord $payment) {
            $this->app->log->notice("Event PluginPaymentAudit {$payment->getAttr('order_no')}");
            UserOrder::change($payment->getAttr('order_no'), $payment);
        });

        // 注册支付拒审事件
        $this->app->event->listen('PluginPaymentRefuse', function (PluginPaymentRecord $payment) {
            $this->app->log->notice("Event PluginPaymentRefuse {$payment->getAttr('order_no')}");
            UserOrder::change($payment->getAttr('order_no'), $payment);
        });

        // 注册支付完成事件
        $this->app->event->listen('PluginPaymentSuccess', function (PluginPaymentRecord $payment) {
            $this->app->log->notice("Event PluginPaymentSuccess {$payment->getAttr('order_no')}");
            UserOrder::change($payment->getAttr('order_no'), $payment);
        });

        // 注册支付取消事件
        $this->app->event->listen('PluginPaymentCancel', function (PluginPaymentRecord $payment) {
            $this->app->log->notice("Event PluginPaymentCancel {$payment->getAttr('order_no')}");
            UserOrder::change($payment->getAttr('order_no'), $payment);
        });

        // 注册订单确认事件
        $this->app->event->listen('PluginPaymentConfirm', function (array $data) {
            $this->app->log->notice("Event PluginPaymentConfirm {$data['order_no']}");
            UserRebate::confirm($data['order_no']);
        });

        // 订单确认收货事件
        $this->app->event->listen('PluginWemallOrderConfirm', function (PluginWemallOrder $order) {
            $this->app->log->notice("Event PluginWemallOrderConfirm {$order->getAttr('order_no')}");
            UserOrder::confirm($order);
        });
    }
}
