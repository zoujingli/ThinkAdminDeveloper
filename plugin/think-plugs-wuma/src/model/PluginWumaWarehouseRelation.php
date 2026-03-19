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

namespace plugin\wuma\model;

use think\model\relation\HasMany;

/**
 * 后关联订单模型.
 *
 * @property int $create_by 上传用户
 * @property string $delete_time 删除时间
 * @property int $id
 * @property int $max 大码数值
 * @property int $mid 中码数值
 * @property string $create_time 创建时间
 * @property PluginWumaWarehouseRelationData[] $mids
 * @property PluginWumaWarehouseRelationData[] $mins
 * @class PluginWumaWarehouseRelation
 */
class PluginWumaWarehouseRelation extends AbstractPrivate
{
    /**
     * 关联中码数据.
     */
    public function mids(): HasMany
    {
        $many = $this->hasMany(PluginWumaWarehouseRelationData::class, 'max', 'max');
        return $many->whereRaw('max>0')->field('max,mid,locked');
    }

    /**
     * 关联小码数据.
     */
    public function mins(): HasMany
    {
        $many = $this->hasMany(PluginWumaWarehouseRelationData::class, 'mid', 'mid');
        return $many->whereRaw('mid>0')->field('mid,min,locked,encode,number');
    }
}
