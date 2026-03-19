<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdminDeveloper
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed: https://mit-license.org
 * | Disclaimer: https://thinkadmin.top/disclaimer
 * | Vip Rights: https://thinkadmin.top/vip-introduce
 * +----------------------------------------------------------------------
 * | Gitee Repository: https://gitee.com/zoujingli/ThinkAdmin
 * | Github Repository: https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

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
