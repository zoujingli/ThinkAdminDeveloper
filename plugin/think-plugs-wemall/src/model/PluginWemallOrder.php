<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
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

namespace plugin\wemall\model;

use plugin\account\model\PluginAccountUser;
use plugin\payment\model\PluginPaymentRecord;
use think\model\relation\HasMany;
use think\model\relation\HasOne;

/**
 * 商城订单主模型
 *
 * @property float $allow_balance 最大余额支付
 * @property float $allow_integral 最大积分抵扣
 * @property float $amount_balance 余额支付
 * @property float $amount_cost 商品成本
 * @property float $amount_discount 折扣后金额
 * @property float $amount_express 快递费用
 * @property float $amount_goods 商品金额
 * @property float $amount_integral 积分抵扣
 * @property float $amount_payment 金额支付
 * @property float $amount_profit 销售利润
 * @property float $amount_real 实际金额
 * @property float $amount_reduct 随机减免
 * @property float $amount_total 订单金额
 * @property float $coupon_amount 优惠券金额
 * @property float $payment_amount 实际支付
 * @property float $ratio_integral 积分兑换比例
 * @property float $rebate_amount 返利金额
 * @property float $reward_balance 奖励余额
 * @property float $reward_integral 奖励积分
 * @property int $cancel_status 取消状态
 * @property int $deleted_status 删除状态(0未删,1已删)
 * @property int $delivery_type 物流类型(0无配送,1需配送)
 * @property int $id
 * @property int $level_agent 升级代理等级
 * @property int $level_member 升级会员等级
 * @property int $number_express 快递计数
 * @property int $number_goods 商品数量
 * @property int $payment_status 支付状态(0未支付,1有支付)
 * @property int $puid1 上1级代理
 * @property int $puid2 上2级代理
 * @property int $puid3 上3级代理
 * @property int $refund_status 售后状态(0未售后,1预订单,2待审核,3待退货,4已退货,5待退款,6已退款,7已完成)
 * @property int $ssid 所属商家
 * @property int $status 流程状态(0已取消,1预订单,2待支付,3待审核,4待发货,5已发货,6已收货,7已评论)
 * @property int $unid 用户编号
 * @property string $cancel_remark 取消描述
 * @property string $cancel_time 取消时间
 * @property string $confirm_remark 签收描述
 * @property string $confirm_time 签收时间
 * @property string $coupon_code 优惠券编号
 * @property string $create_time 创建时间
 * @property string $deleted_remark 删除描述
 * @property string $deleted_time 删除时间
 * @property string $order_no 订单单号
 * @property string $order_ps 订单备注
 * @property string $payment_time 支付时间
 * @property string $refund_code 售后单号
 * @property string $update_time 更新时间
 * @property-read \plugin\account\model\PluginAccountUser $from
 * @property-read \plugin\payment\model\PluginPaymentRecord $payment
 * @property-read \plugin\payment\model\PluginPaymentRecord[] $payments
 * @property-read \plugin\wemall\model\PluginWemallOrderItem[] $items
 * @property-read \plugin\wemall\model\PluginWemallOrderSender $address
 * @property-read \plugin\wemall\model\PluginWemallOrderSender $sender
 * @property-read array $payment_allows
 * @class PluginWemallOrder
 * @package plugin\wemall\model
 */
class PluginWemallOrder extends AbsUser
{
    /**
     * 关联推荐用户
     * @return \think\model\relation\HasOne
     */
    public function from(): HasOne
    {
        return $this->hasOne(PluginAccountUser::class, 'id', 'puid1');
    }

    /**
     * 关联商品数据
     * @return \think\model\relation\HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(PluginWemallOrderItem::class, 'order_no', 'order_no');
    }

    /**
     * 关联支付数据
     * @return \think\model\relation\HasOne
     */
    public function payment(): HasOne
    {
        return $this->hasOne(PluginPaymentRecord::class, 'order_no', 'order_no')->where([
            'payment_status' => 1,
        ]);
    }

    /**
     * 关联支付记录
     * @return \think\model\relation\HasMany
     */
    public function payments(): HasMany
    {
        return $this->hasMany(PluginPaymentRecord::class, 'order_no', 'order_no')->order('id desc')->withoutField('payment_notify');
    }

    /**
     * 关联收货地址
     * @return \think\model\relation\HasOne
     */
    public function address(): HasOne
    {
        return $this->hasOne(PluginWemallOrderSender::class, 'order_no', 'order_no');
    }

    /**
     * 关联发货信息
     * @return \think\model\relation\HasOne
     */
    public function sender(): HasOne
    {
        return $this->hasOne(PluginWemallOrderSender::class, 'order_no', 'order_no');
    }

    /**
     * 格式化支付通道
     * @param mixed $value
     * @return array
     */
    public function getPaymentAllowsAttr($value): array
    {
        $payments = is_string($value) ? str2arr($value) : [];
        return in_array('all', $payments) ? ['all'] : $payments;
    }

    /**
     * 时间格式处理
     * @param mixed $value
     * @return string
     */
    public function getPaymentTimeAttr($value): string
    {
        return $this->getCreateTimeAttr($value);
    }

    /**
     * 时间格式处理
     * @param mixed $value
     * @return string
     */
    public function setPaymentTimeAttr($value): string
    {
        return $this->setCreateTimeAttr($value);
    }

    public function setConfirmTimeAttr($value): string
    {
        return $this->setCreateTimeAttr($value);
    }

    public function getConfirmTimeAttr($value): string
    {
        return $this->getCreateTimeAttr($value);
    }
}