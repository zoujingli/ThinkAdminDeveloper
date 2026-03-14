<?php

declare(strict_types=1);

namespace plugin\wechat\client\model;

use think\admin\Model;
use think\model\relation\HasOne;

class WechatPaymentRefund extends Model
{
    public function record(): HasOne
    {
        return $this->hasOne(WechatPaymentRecord::class, 'code', 'record_code')->with('bindfans');
    }
}
