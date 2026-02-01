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

namespace plugin\wuma\model;

use think\model\relation\HasOne;

/**
 * 赋码批次数据模型.
 *
 * @property int $id
 * @property int $lock 是否已锁定
 * @property int $range_after 结束物码区间
 * @property int $range_start 开始物码区间
 * @property int $real 是否真锁定
 * @property string $batch 赋码批次号
 * @property string $cbatch 物码批次号
 * @property string $create_time 创建时间
 * @property string $pbatch 生产批次号
 * @property string $update_time 更新时间
 * @property PluginWumaSourceAssign $assign
 * @property PluginWumaSourceProduce $bind_produce
 * @property PluginWumaSourceProduce $produce
 * @class PluginWumaSourceBatchAssignItem
 */
class PluginWumaSourceAssignItem extends AbstractPrivate
{
    /**
     * 关联生产模板数据.
     */
    public function produce(): HasOne
    {
        $one = $this->hasOne(PluginWumaSourceProduce::class, 'batch', 'pbatch');
        return $one->with(['bindGoods', 'bindTemplate']);
    }

    /**
     * 关联生产批次数据.
     */
    public function bindProduce(): HasOne
    {
        return $this->produce()->bind([
            'tcode' => 'tcode',
            'tname' => 'tname',
            'ghash' => 'ghash',
            'gcode' => 'gcode',
            'gname' => 'gname',
            'gspec' => 'gspec',
            'gunit' => 'gunit',
            'gcover' => 'gcover',
        ]);
    }

    /**
     * 关联生产模板数据.
     */
    public function assign(): HasOne
    {
        return $this->hasOne(PluginWumaSourceAssign::class, 'batch', 'batch');
    }
}
