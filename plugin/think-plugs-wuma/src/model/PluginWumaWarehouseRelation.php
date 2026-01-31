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

namespace plugin\wuma\model;

use think\model\relation\HasMany;

/**
 * 后关联订单模型.
 *
 * @property int $create_by 上传用户
 * @property int $deleted 删除状态(0未删,1已删)
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
