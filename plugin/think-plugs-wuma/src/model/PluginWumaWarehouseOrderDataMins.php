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

use think\admin\Model;
use think\model\relation\HasOne;

/**
 * 仓库订单小码模型
 *
 * @property int $code 物码数据
 * @property int $ddid 数据编号
 * @property int $id
 * @property int $mode 操作方式(1扫码操作,2虚拟操作)
 * @property int $status 退货:记录状态(0无效,1有效)
 * @property int $stock 调货:库存有效(0已出,1暂存)
 * @property int $type 操作类型(1订单入库,2直接入库,3调货入库,4订单出库,5直接出库,6调货出库,7关联出库,8直接退货)
 * @property string $status_time 状态时间
 * @property-read \plugin\wuma\model\PluginWumaWarehouseOrderData $main
 * @class PluginWumaWarehouseOrderDataMins
 * @package plugin\wuma\model
 */
class PluginWumaWarehouseOrderDataMins extends Model
{
    /**
     * 关联订单数据
     * @return \think\model\relation\HasOne
     */
    public function main(): HasOne
    {
        return $this->hasOne(PluginWumaWarehouseOrderData::class, 'id', 'ddid')->with('main');
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