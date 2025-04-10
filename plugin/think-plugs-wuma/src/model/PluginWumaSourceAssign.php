<?php

// +----------------------------------------------------------------------
// | Wuma Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
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
use think\model\relation\HasOne;

/**
 * 赋码批次模型管理
 *
 * @property int $deleted 删除状态(0未删,1已删)
 * @property int $id
 * @property int $status 记录状态(0无效,1有效)
 * @property int $type 赋码类型(0区间,1关联)
 * @property string $batch 赋码批次号
 * @property string $cbatch 物码批次号
 * @property string $coder_items2 JSON赋码
 * @property string $create_time 创建时间
 * @property string $outer_items JSON出库
 * @property string $update_time 更新时间
 * @property-read \plugin\wuma\model\PluginWumaCodeRule $coder
 * @property-read \plugin\wuma\model\PluginWumaSourceAssignItem[] $range
 * @class PluginWumaSourceAssign
 * @package plugin\wuma\model
 */
class PluginWumaSourceAssign extends AbstractPrivate
{
    /**
     * 关联物码分区数据
     * @return HasMany
     */
    public function range(): HasMany
    {
        return $this->hasMany(PluginWumaSourceAssignItem::class, 'batch', 'batch');
    }

    /**
     * 关联物码批次数据
     * @return \think\model\relation\HasOne
     */
    public function coder(): HasOne
    {
        return $this->hasOne(PluginWumaCodeRule::class, 'batch', 'cbatch');
    }

    /**
     * 查询指定规则的数据列表
     * @param mixed $map
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function lists($map = []): array
    {
        return static::mk()->where($map)->order('id desc')->select()->toArray();
    }
}