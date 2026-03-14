<?php

declare(strict_types=1);

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
