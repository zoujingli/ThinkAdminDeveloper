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
