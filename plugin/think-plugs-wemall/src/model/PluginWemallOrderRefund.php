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

use plugin\wemall\service\UserRefund;
use think\model\relation\HasOne;

/**
 * 商品订单售后模型
 * @class PluginWemallOrderRefund
 * @package plugin\wemall\model
 */
class PluginWemallOrderRefund extends AbsUser
{
    /**
     * 获取订单信息
     * @return \think\model\relation\HasOne
     */
    public function orderinfo(): HasOne
    {
        return $this->hasOne(PluginWemallOrder::class, 'order_no', 'order_no');
    }

    /**
     * 格式化售后图片
     * @param mixed $value
     * @return array
     */
    public function getImagesAttr($value): array
    {
        return is_string($value) ? str2arr($value, '|') : [];
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        if (isset($data['type'])) {
            $data['typename'] = UserRefund::types[$data['type']] ?? $data['type'];
        }
        if (isset($data['reason'])) {
            $data['reasonname'] = UserRefund::reasons[$data['reason']] ?? $data['reason'];
        }
        return $data;
    }
}