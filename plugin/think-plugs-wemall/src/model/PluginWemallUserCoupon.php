<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | Payment Plugin for ThinkAdmin
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

namespace plugin\wemall\model;

use think\model\relation\HasOne;

/**
 * 用户卡券数据.
 *
 * @property int $coid 配置编号
 * @property int $deleted 删除状态(0未删除,1已删除)
 * @property int $expire 有效时间
 * @property int $id
 * @property int $status 生效状态(0未生效,1待使用,2已使用,3已过期)
 * @property int $type 卡券类型
 * @property int $unid 用户UNID
 * @property int $used 使用状态
 * @property string $code 卡券编号
 * @property string $confirm_time 到账时间
 * @property string $create_time 创建时间
 * @property string $expire_time 有效日期
 * @property string $status_desc 状态描述
 * @property string $status_time 修改时间
 * @property string $update_time 更新时间
 * @property string $used_time 使用时间
 * @property PluginWemallConfigCoupon $bind_coupon
 * @property PluginWemallConfigCoupon $coupon
 * @class PluginWemallUserCoupon
 */
class PluginWemallUserCoupon extends AbsUser
{
    /**
     * 关联卡券.
     */
    public function coupon(): HasOne
    {
        return $this->hasOne(PluginWemallConfigCoupon::class, 'id', 'coid');
    }

    /**
     * 绑定卡券.
     */
    public function bindCoupon(): HasOne
    {
        return $this->coupon()->bind([
            'coupon_name' => 'name',
            'coupon_amount' => 'amount',
            'coupon_status' => 'status',
            'coupon_deleted' => 'deleted',
            'limit_times' => 'limit_times',
            'limit_amount' => 'limit_amount',
            'limit_levels' => 'limit_levels',
            'expire_days' => 'expire_days',
        ]);
    }

    /**
     * 数据转换格式.
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
