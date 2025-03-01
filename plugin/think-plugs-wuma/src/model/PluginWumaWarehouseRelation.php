<?php

// +----------------------------------------------------------------------
// | Wuma Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 收费插件 ( https://thinkadmin.top/fee-introduce.html )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wuma
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wuma
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wuma\model;

use think\model\relation\HasMany;

/**
 * 后关联订单模型
 *
 * @property int $create_by 上传用户
 * @property int $deleted 删除状态(0未删,1已删)
 * @property int $id
 * @property int $max 大码数值
 * @property int $mid 中码数值
 * @property string $create_time 创建时间
 * @property-read \plugin\wuma\model\PluginWumaWarehouseRelationData[] $mids
 * @property-read \plugin\wuma\model\PluginWumaWarehouseRelationData[] $mins
 * @class PluginWumaWarehouseRelation
 * @package plugin\wuma\model
 */
class PluginWumaWarehouseRelation extends AbstractPrivate
{
    /**
     * 关联中码数据
     * @return \think\model\relation\HasMany
     */
    public function mids(): HasMany
    {
        $many = $this->hasMany(PluginWumaWarehouseRelationData::class, 'max', 'max');
        return $many->whereRaw('max>0')->field('max,mid,locked');
    }

    /**
     * 关联小码数据
     * @return \think\model\relation\HasMany
     */
    public function mins(): HasMany
    {
        $many = $this->hasMany(PluginWumaWarehouseRelationData::class, 'mid', 'mid');
        return $many->whereRaw('mid>0')->field('mid,min,locked,encode,number');
    }
}