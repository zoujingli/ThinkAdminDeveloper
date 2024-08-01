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
 * 仓库订单数据模型
 * @class PluginWumaWarehouseOrderData
 * @package plugin\wuma\model
 */
class PluginWumaWarehouseOrderData extends AbstractPrivate
{
    /**
     * 关联订单数据
     * @return HasOne
     */
    public function main(): HasOne
    {
        return $this->hasOne(PluginWumaWarehouseOrder::class, 'code', 'code')->with(['bindGoods', 'bindWarehouse']);
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
}