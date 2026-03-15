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

namespace plugin\wemall\model;

use plugin\account\model\PluginAccountUser;
use plugin\payment\model\PluginPaymentRecord;
use think\model\relation\HasMany;
use think\model\relation\HasOne;

class PluginWemallOrder extends AbsUser
{
    public function from(): HasOne
    {
        return $this->hasOne(PluginAccountUser::class, 'id', 'puid1');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PluginWemallOrderItem::class, 'order_no', 'order_no');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(PluginPaymentRecord::class, 'order_no', 'order_no')->where([
            'payment_status' => 1,
        ]);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PluginPaymentRecord::class, 'order_no', 'order_no')->order('id desc')->withoutField('payment_notify');
    }

    public function address(): HasOne
    {
        return $this->hasOne(PluginWemallOrderSender::class, 'order_no', 'order_no');
    }

    public function sender(): HasOne
    {
        return $this->hasOne(PluginWemallOrderSender::class, 'order_no', 'order_no');
    }

    public function getPaymentAllowsAttr($value): array
    {
        $payments = is_string($value) ? str2arr($value) : [];
        return in_array('all', $payments, true) ? ['all'] : $payments;
    }
}
