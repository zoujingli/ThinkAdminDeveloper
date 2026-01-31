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

namespace plugin\wemall\service;

use plugin\payment\model\PluginPaymentAddress;
use plugin\payment\model\PluginPaymentRecord;
use plugin\payment\service\Payment;
use plugin\wemall\model\PluginWemallConfigDiscount;
use plugin\wemall\model\PluginWemallOrder;
use plugin\wemall\model\PluginWemallOrderItem;
use plugin\wemall\model\PluginWemallOrderSender;
use plugin\wemall\model\PluginWemallUserCreate;
use plugin\wemall\model\PluginWemallUserRelation;
use think\admin\Exception;
use think\admin\Library;

/**
 * 商城订单数据服务
 * @class UserOrder
 * @package plugin\wemall\service
 */
abstract class UserOrder
{
    /**
     * 获取随减金额
     * @return float
     * @throws \think\admin\Exception
     */
    public static function reduct(): float
    {
        $config = sysdata('plugin.wemall.config');
        if (empty($config['enable_reduct'])) return 0.00;
        $min = intval(($config['reduct_min'] ?? 0) * 100);
        $max = intval(($config['reduct_max'] ?? 0) * 100);
        return mt_rand($min, $max) / 100;
    }

    /**
     * 同步订单关联商品的库存
     * @param string $orderNo 订单编号
     * @return boolean
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function stock(string $orderNo): bool
    {
        $map = ['order_no' => $orderNo];
        $codes = PluginWemallOrderItem::mk()->where($map)->column('gcode');
        foreach (array_unique($codes) as $code) GoodsService::stock($code);
        return true;
    }

    /**
     * 获取订单模型
     * @param PluginWemallOrder|string $order
     * @param ?integer $unid 动态绑定变量
     * @param ?string $orderNo 动态绑定变量
     * @return \plugin\wemall\model\PluginWemallOrder
     * @throws \think\admin\Exception
     */
    public static function widthOrder($order, ?int &$unid = 0, ?string &$orderNo = ''): PluginWemallOrder
    {
        if (is_string($order)) {
            $order = PluginWemallOrder::mk()->where(['order_no' => $order])->findOrEmpty();
        }
        if ($order instanceof PluginWemallOrder) {
            [$unid, $orderNo] = [intval($order->getAttr('unid')), $order->getAttr('order_no')];
            return $order;
        }
        throw new Exception("无效订单对象！");
    }

    /**
     * 根据订单更新会员等级
     * @param string|PluginWemallOrder $order
     * @return array|null [RELATION, ORDER, ENTRY]
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function upgrade($order): ?array
    {
        // 目标订单数据
        $order = self::widthOrder($order);
        if ($order->isEmpty() || $order->getAttr('status') < 4) return null;
        // 会员用户数据
        $where = ['unid' => $order->getAttr('unid')];
        $relation = PluginWemallUserRelation::mk()->where($where)->findOrEmpty();
        if ($relation->isEmpty()) return null;
        // 更新入会资格
        $entry = self::entry($relation);
        // 尝试绑定代理
        if (empty($relation['puids']) && $order->getAttr('puid1') > 0) {
            $puid1 = $order->getAttr('puid1') > 0 ? $order->getAttr('puid1') : $relation['puid1'];
            UserUpgrade::bindAgent($relation['unid'], intval($puid1));
        }
        // 重置订单推荐
        if ($relation->refresh() && $relation['puid1'] > 0) {
            $order->save(['puid1' => $relation['puid1'], 'puid2' => $relation['puid2'], 'puid3' => $relation['puid3']]);
        }
        // 刷新会员等级
        UserUpgrade::upgrade($relation['unid'], true, $order->getAttr('order_no'));
        // 刷新代理等级
        if ($entry->getAttr('entry_agent')) {
            UserAgent::upgrade($relation['unid'], true, $order->getAttr('order_no'));
        }
        // 返回操作数据
        return [$relation->toArray(), $order->toArray(), $entry];
    }

    /**
     * 刷新用户入会礼包
     * @param int|PluginWemallUserRelation $unid
     * @return PluginWemallUserRelation
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function entry($unid): PluginWemallUserRelation
    {
        [$relation, $unid] = PluginWemallUserRelation::withRelation($unid);
        // 订单升级等级
        $query = PluginWemallOrder::mk()->whereRaw('status>3 and refund_status<4');
        $query->field(['max(level_agent)' => 'agent', 'max(level_member)' => 'member']);
        $entry = $query->where(['unid' => $unid, 'payment_status' => 1])->findOrEmpty();
        // 更新用户入会
        $enterAgent = intval(!empty($entry['agent']));
        $enterMember = intval((is_numeric($entry['member']) ? $entry['member'] : -1) > -1);
        // 代理权限还需要检查后台创建的用户表
        if (empty($enterAgent)) {
            $map = ['unid' => $unid, 'agent_entry' => 1, 'status' => 1, 'deleted' => 0];
            if (PluginWemallUserCreate::mk()->where($map)->findOrEmpty()->isExists()) {
                $enterAgent = 1;
            }
        }
        $relation->save(['entry_agent' => $enterAgent, 'entry_member' => $enterMember]);
        // 触发代理注册
        $event = $enterAgent ? 'PluginWemallAgentCreate' : 'PluginWemallAgentCancel';
        Library::$sapp->event->trigger($event, $relation);
        // 返回用户信息
        return $relation;
    }

    /**
     * 获取等级折扣比例
     * @param integer $disId 折扣方案ID
     * @param integer $levelCode 等级序号
     * @param float $disRate 默认比例
     * @return array [方案编号, 折扣比例]
     */
    public static function discount(int $disId, int $levelCode, float $disRate = 100.00): array
    {
        if ($disId > 0) {
            $where = ['id' => $disId, 'status' => 1, 'deleted' => 0];
            $discount = PluginWemallConfigDiscount::mk()->where($where)->findOrEmpty();
            if ($discount->isExists()) foreach ($discount['items'] as $vo) {
                if ($vo['level'] == $levelCode) $disRate = floatval($vo['discount']);
            }
        }
        return [$disId, $disRate];
    }

    /**
     * 更新订单收货地址
     * @param \plugin\wemall\model\PluginWemallOrder $order
     * @param \plugin\payment\model\PluginPaymentAddress $address
     * @return boolean
     * @throws \think\admin\Exception
     */
    public static function perfect(PluginWemallOrder $order, PluginPaymentAddress $address): bool
    {
        $unid = $order->getAttr('unid');
        $orderNo = $order->getAttr('order_no');
        // 根据地址计算运费
        $map1 = ['order_no' => $orderNo, 'status' => 1, 'deleted' => 0];
        $map2 = ['order_no' => $order->getAttr('order_no'), 'unid' => $unid];
        [$amount, $tCount, $tCode, $remark] = ExpressService::amount(
            PluginWemallOrderItem::mk()->where($map1)->column('delivery_code'),
            $address->getAttr('region_prov'), $address->getAttr('region_city'),
            (int)PluginWemallOrderItem::mk()->where($map2)->sum('delivery_count')
        );
        // 创建订单发货信息
        $data = [
            'delivery_code'   => $tCode, 'delivery_count' => $tCount, 'unid' => $unid,
            'delivery_remark' => $remark, 'delivery_amount' => $amount, 'status' => 1,
        ];
        $data['order_no'] = $orderNo;
        $data['address_id'] = $address->getAttr('id');
        // 收货人信息
        $data['user_name'] = $address->getAttr('user_name');
        $data['user_phone'] = $address->getAttr('user_phone');
        $data['user_idcode'] = $address->getAttr('idcode');
        $data['user_idimg1'] = $address->getAttr('idimg1');
        $data['user_idimg2'] = $address->getAttr('idimg2');
        // 收货地址信息
        $data['region_prov'] = $address->getAttr('region_prov');
        $data['region_city'] = $address->getAttr('region_city');
        $data['region_area'] = $address->getAttr('region_area');
        $data['region_addr'] = $address->getAttr('region_addr');
        // 记录原地址信息
        $data['extra'] = $data;
        PluginWemallOrderSender::mk()->where(['order_no' => $orderNo])->findOrEmpty()->save($data);
        // 组装更新订单数据, 重新计算订单金额
        $update = ['status' => 2, 'amount_express' => $data['delivery_amount']];
        $update['amount_real'] = round($order->getAttr('amount_discount') + $amount - $order->getAttr('amount_reduct'), 2);
        $update['amount_total'] = round($order->getAttr('amount_goods') + $amount, 2);
        // 支付金额不能少于零
        if ($update['amount_real'] <= 0) $update['amount_real'] = 0.00;
        if ($update['amount_total'] <= 0) $update['amount_total'] = 0.00;
        // 更新用户订单数据
        if ($order->save($update)) {
            // 触发订单确认事件
            Library::$sapp->event->trigger('PluginWemallOrderPerfect', $order);
            // 返回处理成功数据
            return true;
        } else {
            return false;
        }
    }

    /**
     * 更新订单支付状态
     * @param PluginWemallOrder|string $order 订单模型
     * @param PluginPaymentRecord $payment 支付行为记录
     * @return array|bool|string|void|null
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @remark 订单状态(0已取消,1预订单,2待支付,3待审核,4待发货,5已发货,6已收货,7已评论)
     */
    public static function change($order, PluginPaymentRecord $payment)
    {
        $order = self::widthOrder($order);
        if ($order->isEmpty()) return null;

        // 同步订单支付统计
        $ptotal = Payment::totalPaymentAmount($payment->getAttr('order_no'));
        $order->appendData([
            'payment_time'    => $payment->getAttr('create_time'),
            'payment_amount'  => $ptotal['amount'] ?? 0,
            'amount_payment'  => $ptotal['payment'] ?? 0,
            'amount_balance'  => $ptotal['balance'] ?? 0,
            'amount_integral' => $ptotal['integral'] ?? 0,
        ], true);

        // 检查订单是否已取消或退款
        if ($order->getAttr('cancel_status') > 0 || $order->getAttr('refund_status') > 0) {
            return $order->save();
        }
        
        // 订单已经支付完成
        if ($order->getAttr('payment_amount') >= $order->getAttr('amount_real')) {
            // 已完成支付，更新订单状态
            $status = $order->getAttr('delivery_type') ? 4 : 5;
            $order->save(['status' => $status, 'payment_status' => 1]);
            // 确认完成支付，发放余额积分奖励及升级返佣
            return static::payment($order);
        }

        // 退款或部分退款，仅更新订单支付统计
        if ($payment->getAttr('refund_status')) {
            // 退回优惠券
            if ($payment->getAttr('channel_code') === Payment::COUPON) {
                UserCoupon::resume($payment->getAttr('payment_trade'));
            }
            return $order->save();
        }

        // 提交支付凭证，只需更新订单状态为【待审核】
        $isVoucher = $payment->getAttr('channel_type') === Payment::VOUCHER;
        if ($isVoucher && $payment->getAttr('audit_status') === 1) {
            return $order->save(['status' => 3, 'payment_status' => 1]);
        }

        // 凭证支付审核被拒绝，订单回滚到未支付状态
        if ($isVoucher && $payment->getAttr('audit_status') === 0) {
            if ($order->getAttr('status') === 3) $order->save(['status' => 2]);
            return self::upgrade($order);
        } else {
            $order->save();
        }
    }

    /**
     * 取消订单撤销奖励
     * @param PluginWemallOrder|string $order
     * @param boolean $setRebate 更新返佣
     * @return string
     */
    public static function cancel($order, bool $setRebate = false): string
    {
        $code = '';
        try { /* 创建用户奖励 */
            $order = UserReward::cancel($order, $code);
        } catch (\Exception $exception) {
            trace_file($exception);
        }
        if ($setRebate) try { /* 订单返佣处理 */
            UserRebate::cancel($order);
        } catch (\Exception $exception) {
            trace_file($exception);
        }
        try { /* 升级会员等级 */
            UserUpgrade::upgrade(intval($order->getAttr('unid')));
        } catch (\Exception $exception) {
            trace_file($exception);
        }
        return $code;
    }

    /**
     * 支付成功发放奖励
     * @param PluginWemallOrder|string $order
     * @return string
     */
    public static function payment($order): string
    {
        $code = '';
        try { /* 创建用户奖励 */
            UserReward::create($order, $code);
        } catch (\Exception $exception) {
            trace_file($exception);
        }
        try { /* 订单返佣处理 */
            UserRebate::create($order);
        } catch (\Exception $exception) {
            trace_file($exception);
        }
        try { /* 升级会员等级 */
            self::upgrade($order);
        } catch (\Exception $exception) {
            trace_file($exception);
        }
        // 返回奖励单号
        return $code;
    }

    /**
     * 支付成功发放奖励
     * @param PluginWemallOrder|string $order
     * @return string
     */
    public static function confirm($order): string
    {
        $code = '';
        try { /* 创建用户奖励 */
            UserReward::confirm($order, $code);
        } catch (\Exception $exception) {
            trace_file($exception);
        }
        try { /* 订单返佣处理 */
            UserRebate::confirm($order);
        } catch (\Exception $exception) {
            trace_file($exception);
        }
        // 返回奖励单号
        return $code;
    }
}