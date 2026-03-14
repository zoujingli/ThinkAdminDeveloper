<?php

declare(strict_types=1);

namespace plugin\payment\model;

use plugin\account\model\Abs;
use plugin\account\model\PluginAccountBind;
use plugin\account\model\PluginAccountUser;
use plugin\payment\service\Payment;
use think\model\relation\HasOne;

class PluginPaymentRecord extends Abs
{
    protected $deleteTime = false;

    public function user(): HasOne
    {
        return $this->hasOne(PluginAccountUser::class, 'id', 'unid');
    }

    public function device(): HasOne
    {
        return $this->hasOne(PluginAccountBind::class, 'id', 'usid');
    }

    public function getUserAttr($value): array
    {
        return is_array($value) ? $value : [];
    }

    public function setPaymentNotifyAttr($value): string
    {
        return $this->setExtraAttr($value);
    }

    public function getPaymentNotifyAttr($value): array
    {
        return $this->getExtraAttr($value);
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        if (isset($data['channel_type'])) {
            $data['channel_type_name'] = Payment::typeName($data['channel_type']);
        }
        return $data;
    }
}
