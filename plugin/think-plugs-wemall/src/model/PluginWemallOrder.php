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