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

use plugin\account\model\Abs;
use think\model\relation\HasMany;

/**
 * 商城卡券模型
 * @class PluginWemallConfigCoupon
 * @package plugin\wemall\model
 */
class PluginWemallConfigCoupon extends Abs
{
    // 卡券类型
    public const types = ['通用券', '商品券'];

    /**
     * 关联自己的卡券
     * @return \think\model\relation\HasMany
     */
    public function usable(): HasMany
    {
        return $this->hasMany(PluginWemallUserCoupon::class, 'coid', 'id')->where(['deleted' => 0]);
    }

    /**
     * 获取等级限制
     * @param mixed $value
     * @return array
     */
    public function getLimitLevelsAttr($value): array
    {
        return is_string($value) ? str2arr($value) : [];
    }

    /**
     * 设置等级限制
     * @param mixed $value
     * @return string
     */
    public function setLimitLevelsAttr($value): string
    {
        return is_array($value) ? arr2str($value) : $value;
    }

    /**
     * 输出格式化数据
     * @return array
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