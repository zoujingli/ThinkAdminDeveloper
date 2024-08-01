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

use think\model\relation\HasOne;

/**
 * 用户卡券数据
 * @class PluginWemallUserCoupon
 * @package plugin\wemall\model
 */
class PluginWemallUserCoupon extends AbsUser
{

    /**
     * 关联卡券
     * @return \think\model\relation\HasOne
     */
    public function coupon(): HasOne
    {
        return $this->hasOne(PluginWemallConfigCoupon::class, 'id', 'coid');
    }

    /**
     * 绑定卡券
     * @return HasOne
     */
    public function bindCoupon(): HasOne
    {
        return $this->coupon()->bind([
            'coupon_name'    => 'name',
            'coupon_amount'  => 'amount',
            'coupon_status'  => 'status',
            'coupon_deleted' => 'deleted',
            'limit_times'    => 'limit_times',
            'limit_amount'   => 'limit_amount',
            'limit_levels'   => 'limit_levels',
            'expire_days'    => 'expire_days',
        ]);
    }

    /**
     * 数据转换格式
     * @return array
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        if (isset($data['type'])) {
            $data['type_name'] = PluginWemallConfigCoupon::types[$data['type']] ?? $data['type'];
        }
        return $data;
    }
}