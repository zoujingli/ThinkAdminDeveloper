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

/**
 * 仓库订单模型
 * @class PluginWumaWarehouseOrder
 * @package plugin\wuma\model
 */
class PluginWumaWarehouseOrder extends AbstractPrivate
{

    // 入库订单类型
    public const interTypes = [1, 2, 3];

    // 出库订单类型
    public const outerTypes = [4, 5, 6, 7];

    // 退货订单状态
    public const returnTypes = [8];

    /**
     * 关联商品数据
     * @return \think\model\relation\HasOne
     */
    public function goods(): HasOne
    {
        return $this->hasOne(PluginWemallGoodsItem::class, 'ghash', 'ghash')->with('bindGoods');
    }

    /**
     * 出库搜索器
     * @param mixed $query
     * @return void
     */
    public function searchOuterAttr($query)
    {
        $query->whereIn('type', PluginWumaWarehouseOrder::outerTypes);
    }

    /**
     * 入库搜索器
     * @param mixed $query
     * @return void
     */
    public function searchInterAttr($query)
    {
        $query->whereIn('type', PluginWumaWarehouseOrder::interTypes);
    }

    /**
     * 退货搜索器
     * @param mixed $query
     * @return void
     */
    public function searchReturnAttr($query)
    {
        $query->whereIn('type', PluginWumaWarehouseOrder::returnTypes);
    }

    /**
     * 绑定产品数据
     * @return HasOne
     */
    public function bindGoods(): HasOne
    {
        return $this->goods()->bind([
            'gcode'  => 'gcode',
            'gname'  => 'gname',
            'gunit'  => 'gunit',
            'gspec'  => 'gspec',
            'gcover' => 'gcover',
        ]);
    }

    /**
     * 关联仓库数据
     * @return \think\model\relation\HasOne
     */
    public function warehouse(): HasOne
    {
        return $this->hasOne(PluginWumaWarehouse::class, 'code', 'wcode');
    }

    /**
     * 绑定仓库数据
     * @return \think\model\relation\HasOne
     */
    public function bindWarehouse(): HasOne
    {
        return $this->warehouse()->bind([
            'wname'    => 'name',
            'wperson'  => 'person',
            'wprov'    => 'addr_prov',
            'wcity'    => 'addr_city',
            'warea'    => 'addr_area',
            'wstatus'  => 'status',
            'wdeleted' => 'deleted'
        ]);
    }
}