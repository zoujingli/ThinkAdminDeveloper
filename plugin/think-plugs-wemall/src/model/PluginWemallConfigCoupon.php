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

namespace plugin\wemall\model;

use plugin\account\model\Abs;
use think\model\relation\HasMany;

/**
 * 商城卡券模型.
 *
 * @property float $amount 抵扣金额
 * @property float $limit_amount 金额门槛(0不限制)
 * @property int $deleted 删除状态(1已删,0未删)
 * @property int $expire_days 有效天数
 * @property int $id
 * @property int $limit_times 限领数量(0不限制)
 * @property int $sort 排序权重
 * @property int $status 卡券状态(0禁用,1使用)
 * @property int $total_sales 发放数量
 * @property int $total_stock 库存数量
 * @property int $total_used 使用数量
 * @property int $type 类型(0通用券,1商品券)
 * @property string $content 内容描述
 * @property string $cover 封面图标
 * @property string $create_time 创建时间
 * @property string $extra 扩展数据
 * @property string $limit_levels 授权等级
 * @property string $name 优惠名称
 * @property string $remark 系统备注
 * @property string $update_time 更新时间
 * @property PluginWemallUserCoupon[] $usable
 * @class PluginWemallConfigCoupon
 */
class PluginWemallConfigCoupon extends Abs
{
    // 卡券类型
    public const types = ['通用券', '商品券'];

    /**
     * 关联自己的卡券.
     */
    public function usable(): HasMany
    {
        return $this->hasMany(PluginWemallUserCoupon::class, 'coid', 'id')->where(['deleted' => 0]);
    }

    /**
     * 获取等级限制.
     * @param mixed $value
     */
    public function getLimitLevelsAttr($value): array
    {
        return is_string($value) ? str2arr($value) : [];
    }

    /**
     * 设置等级限制.
     * @param mixed $value
     */
    public function setLimitLevelsAttr($value): string
    {
        return is_array($value) ? arr2str($value) : $value;
    }

    /**
     * 输出格式化数据.
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        if (isset($data['type'])) {
            $data['type_name'] = self::types[$data['type']] ?? $data['type'];
        }
        return $data;
    }
}
