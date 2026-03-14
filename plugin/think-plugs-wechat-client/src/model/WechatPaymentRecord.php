<?php

declare(strict_types=1);

namespace plugin\wechat\client\model;

use plugin\wechat\client\service\PaymentService;
use think\admin\Model;
use think\model\relation\HasOne;

class WechatPaymentRecord extends Model
{
    public function fans(): HasOne
    {
        return $this->hasOne(WechatFans::class, 'openid', 'openid');
    }

    public function bindFans(): HasOne
    {
        return $this->fans()->bind([
            'fans_headimg' => 'headimgurl',
            'fans_nickname' => 'nickname',
        ]);
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        $data['type_name'] = PaymentService::tradeTypeNames[$data['type']] ?? $data['type'];
        return $data;
    }
}
