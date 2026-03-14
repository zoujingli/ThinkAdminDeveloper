<?php

declare(strict_types=1);

namespace plugin\payment\model;

use plugin\account\model\Abs;
use plugin\account\model\PluginAccountUser;
use think\model\relation\HasOne;

class PluginPaymentRefund extends Abs
{
    protected $deleteTime = false;

    public function user(): HasOne
    {
        return $this->hasOne(PluginAccountUser::class, 'id', 'unid');
    }

    public function record(): HasOne
    {
        return $this->hasOne(PluginPaymentRecord::class, 'code', 'record_code');
    }
}
