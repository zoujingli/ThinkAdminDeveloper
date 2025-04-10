<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wemall
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wemall
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wemall;

use plugin\account\model\PluginAccountUser;
use plugin\account\service\Account;
use plugin\payment\model\PluginPaymentRecord;
use plugin\payment\Service as PaymentService;
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
 * 插件服务注册
 * @class Service
 * @package plugin\wemall
 */
class Service extends Plugin
{
    /**
     * 定义插件名称
     * @var string
     */
    protected $appName = '分销商城';

    /**
     * 定义安装包名
     * @var string
     */
    protected $package = 'zoujingli/think-plugs-wemall';

    /**
     * 插件服务注册
     * @return void
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
                $where = ['deleted' => 0];
                if (preg_match('/^1\d{10}$/', $input['fphone'])) {
                    $where['phone'] = $input['fphone'];
                } else {
                    if (empty($input['from'])) $showError('无效推荐人');
                    $where['id'] = $input['from'];
                }
                // 判断推荐人是否可
                $from = PluginAccountUser::mk()->where($where)->findOrEmpty();
                if ($from->isEmpty()) $showError('无效邀请人！');
                if ($from->getAttr('phone') == $input['phone']) $showError('不能邀请自己！');
                [$rela] = PluginWemallUserRelation::withRelation($from->getAttr('id'));
                if (empty($rela['entry_agent'])) $showError('无邀请权限！');
                // 检查自己是否已绑定
                $where = ['phone' => $input['phone'], 'deleted' => 0];
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
            if (!empty($input['fphone'])) try {
                $map = ['deleted' => 0];
                if (preg_match('/^1\d{10}$/', $input['fphone'])) {
                    $map['phone'] = $input['fphone'];
                } else {
                    $map['id'] = $input['from'] ?? 0;
                }
                $from = PluginAccountUser::mk()->where($map)->value('id');
                if ($from > 0) UserUpgrade::bindAgent(intval($data['unid']), $from, 0);
            } catch (\Exception $exception) {
                trace_file($exception);
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

    /**
     * 定义插件菜单
     * @return array[]
     */
    public static function menu(): array
    {
        $code = self::getAppCode();
        return array_merge([
            [
                'name' => '商城配置',
                'subs' => [
                    ['name' => '数据统计报表', 'icon' => 'layui-icon layui-icon-theme', 'node' => "{$code}/base.report/index"],
                    ['name' => '系统通知管理', 'icon' => 'layui-icon layui-icon-email', 'node' => "{$code}/base.notify/index"],
                    ['name' => '商城参数管理', 'icon' => 'layui-icon layui-icon-set', 'node' => "{$code}/base.config/index"],
                    ['name' => '推广海报管理', 'icon' => 'layui-icon layui-icon-carousel', 'node' => "{$code}/base.poster/index"],
                    ['name' => '店铺页面装修', 'icon' => 'layui-icon layui-icon-code-circle', 'node' => "{$code}/base.design/index"],
                    ['name' => '快递公司管理', 'icon' => 'layui-icon layui-icon-website', 'node' => "{$code}/base.express.company/index"],
                    ['name' => '邮费模板管理', 'icon' => 'layui-icon layui-icon-template-1', 'node' => "{$code}/base.express.template/index"],
                ],
            ],
            [
                'name' => '用户管理',
                'subs' => [
                    ['name' => '会员等级管理', 'icon' => 'layui-icon layui-icon-water', 'node' => "{$code}/base.level/index"],
                    ['name' => '会员折扣方案', 'icon' => 'layui-icon layui-icon-engine', 'node' => "{$code}/base.discount/index"],
                    ['name' => '会员用户管理', 'icon' => 'layui-icon layui-icon-user', 'node' => "{$code}/user.admin/index"],
                    // ['name' => '用户卡券管理', 'icon' => 'layui-icon layui-icon-tabs', 'node' => "{$code}/user.coupon/index"],
                    ['name' => '创建会员用户', 'icon' => 'layui-icon layui-icon-tabs', 'node' => "{$code}/user.create/index"],
                    ['name' => '用户余额充值', 'icon' => 'layui-icon layui-icon-rmb', 'node' => "{$code}/user.recharge/index"],
                ],
            ],

            [
                'name' => '商城管理',
                'subs' => [
                    ['name' => '商品数据管理', 'icon' => 'layui-icon layui-icon-star', 'node' => "{$code}/shop.goods/index"],
                    ['name' => '订单数据管理', 'icon' => 'layui-icon layui-icon-template', 'node' => "{$code}/shop.order/index"],
                    ['name' => '订单发货管理', 'icon' => 'layui-icon layui-icon-transfer', 'node' => "{$code}/shop.sender/index"],
                    ['name' => '售后订单管理', 'icon' => 'layui-icon layui-icon-util', 'node' => "{$code}/shop.refund/index"],
                    ['name' => '商品评论管理', 'icon' => 'layui-icon layui-icon-util', 'node' => "{$code}/shop.reply/index"],
                ],
            ],
            [
                'name' => '代理管理',
                'subs' => [
                    ['name' => '代理等级管理', 'icon' => 'layui-icon layui-icon-water', 'node' => "{$code}/base.agent/index"],
                    ['name' => '代理返佣管理', 'icon' => 'layui-icon layui-icon-transfer', 'node' => "{$code}/user.rebate/index"],
                    ['name' => '代理提现管理', 'icon' => 'layui-icon layui-icon-diamond', 'node' => "{$code}/user.transfer/index"],
                    // ['name' => '活动签到管理', 'icon' => 'layui-icon layui-icon-engine', 'node' => "{$code}/user.checkin/index"],
                ]
            ],
            [
                'name' => '帮助咨询',
                'subs' => [
                    ['name' => '常见问题管理', 'icon' => 'layui-icon layui-icon-star', 'node' => "{$code}/help.problem/index"],
                    ['name' => '意见反馈管理', 'icon' => 'layui-icon layui-icon-template', 'node' => "{$code}/help.feedback/index"],
                    // ['name' => '工单提问管理', 'icon' => 'layui-icon layui-icon-service', 'node' => "{$code}/help.question/index"],
                ],
            ],
        ], PaymentService::menu());
    }
}