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

namespace plugin\wemall\service;

use plugin\payment\service\Balance;
use plugin\payment\service\Integral;
use plugin\wemall\model\PluginWemallOrder;
use think\admin\Exception;

/**
 * 商城订单奖励.
 * @class UserReward
 */
abstract class UserReward
{
    /**
     * 创建用户奖励.
     * @param PluginWemallOrder|string $order
     * @param null|string $code 奖励编号
     * @throws Exception
     */
    public static function create($order, ?string &$code = ''): PluginWemallOrder
    {
        $order = UserOrder::widthOrder($order, $unid, $orderNo);
        if ($order->isEmpty() && $order->getAttr('status') < 4) {
            throw new Exception('订单状态异常');
        }
        // 生成奖励编号
        $code = $code ?: "CZ{$order->getAttr('order_no')}";
        // 确认奖励余额
        if ($order->getAttr('reward_balance') > 0) {
            $remark = "来自订单 {$order->getAttr('order_no')} 奖励 {$order->getAttr('reward_balance')} 余额";
            Balance::create($order->getAttr('unid'), $code, '购物奖励余额', strval($order->getAttr('reward_balance')), $remark, true);
        }
        // 确认奖励积分
        if ($order->getAttr('reward_integral') > 0) {
            $remark = "来自订单 {$order->getAttr('order_no')} 奖励 {$order->getAttr('reward_integral')} 积分";
            Integral::create($order->getAttr('unid'), $code, '购物奖励积分', strval($order->getAttr('reward_integral')), $remark, true);
        }
        // 返回订单模型
        return $order;
    }

    /**
     * 确认发放奖励.
     * @param PluginWemallOrder|string $order
     * @param null|string $code 奖励编号
     * @throws Exception
     */
    public static function confirm($order, ?string &$code = ''): PluginWemallOrder
    {
        $order = UserOrder::widthOrder($order, $unid, $orderNo);
        if ($order->isEmpty() && $order->getAttr('status') < 4) {
            throw new Exception('订单状态异常');
        }
        // 生成奖励编号
        $code = $code ?: "CZ{$order->getAttr('order_no')}";
        Balance::unlock($code) && Integral::unlock($code);
        // 返回订单模型
        return $order;
    }

    /**
     * 取消订单奖励.
     * @param PluginWemallOrder|string $order
     * @param null|string $code 奖励编号
     * @throws Exception
     */
    public static function cancel($order, ?string &$code = ''): PluginWemallOrder
    {
        $order = UserOrder::widthOrder($order, $unid, $orderNo);
        if ($order->isEmpty() && $order->getAttr('status') > 0) {
            throw new Exception('订单状态异常');
        }
        // 生成奖励编号
        $code = $code ?: "CZ{$order->getAttr('order_no')}";
        // 取消余额奖励 及 积分奖励
        if ($order->getAttr('reward_balance') > 0) {
            Balance::cancel($code);
        }
        if ($order->getAttr('reward_integral') > 0) {
            Integral::cancel($code);
        }
        // 返回订单模型
        return $order;
    }
}
