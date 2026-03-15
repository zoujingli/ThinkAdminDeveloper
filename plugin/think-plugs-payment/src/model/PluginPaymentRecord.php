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
