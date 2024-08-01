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

use plugin\wemall\model\PluginWemallGoodsItem;
use think\model\relation\HasOne;

class PluginWumaSalesOrder extends AbstractPrivate
{
    /**
     * 关联代理数据
     * @return \think\model\relation\HasOne
     */
    public function agent(): HasOne
    {
        return $this->hasOne(PluginWumaSalesUser::class, 'id', 'auid');
    }

    /**
     * 关联代理数据
     * @return \think\model\relation\HasOne
     */
    public function fromer(): HasOne
    {
        return $this->hasOne(PluginWumaSalesUser::class, 'id', 'xuid');
    }

    /**
     * 关联商品数据
     * @return \think\model\relation\HasOne
     */
    public function goods(): HasOne
    {
        return $this->hasOne(PluginWemallGoodsItem::class, 'ghash', 'ghash')->with('bindGoods');
    }

    /**
     * 绑定商品数据
     * @return \think\model\relation\HasOne
     */
    public function bindGoods(): HasOne
    {
        return $this->goods()->bind([
            'gunit'    => 'gunit',
            'gcode'    => "gcode",
            'gname'    => 'gname',
            'gspec'    => 'gspec',
            'gcover'   => 'gcover',
            'gstatus'  => 'gstatus',
            'gdeleted' => 'gdeleted',
        ]);
    }
}