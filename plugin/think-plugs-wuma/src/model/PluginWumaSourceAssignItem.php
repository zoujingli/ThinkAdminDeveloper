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

use think\model\relation\HasOne;

/**
 * 赋码批次数据模型
 * @class PluginWumaSourceBatchAssignItem
 * @package plugin\wuma\model
 */
class PluginWumaSourceAssignItem extends AbstractPrivate
{
    /**
     * 关联生产模板数据
     * @return HasOne
     */
    public function produce(): HasOne
    {
        $one = $this->hasOne(PluginWumaSourceProduce::class, 'batch', 'pbatch');
        return $one->with(['bindGoods', 'bindTemplate']);
    }

    /**
     * 关联生产批次数据
     * @return \think\model\relation\HasOne
     */
    public function bindProduce(): HasOne
    {
        return $this->produce()->bind([
            'tcode'  => 'tcode',
            'tname'  => 'tname',
            'ghash'  => 'ghash',
            'gcode'  => 'gcode',
            'gname'  => 'gname',
            'gspec'  => 'gspec',
            'gunit'  => 'gunit',
            'gcover' => 'gcover',
        ]);
    }

    /**
     * 关联生产模板数据
     * @return HasOne
     */
    public function assign(): HasOne
    {
        return $this->hasOne(PluginWumaSourceAssign::class, 'batch', 'batch');
    }
}