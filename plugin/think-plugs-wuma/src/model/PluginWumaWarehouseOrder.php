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

use plugin\wemall\model\PluginWemallGoodsItem;
use think\model\relation\HasOne;

/**
 * 仓库订单模型.
 *
 * @property int $auid 出库代理
 * @property int $deleted 删除状态(0未删,1已删)
 * @property int $id
 * @property int $mode 操作方式(1扫码操作,2虚拟操作)
 * @property int $num_need 扫码总数
 * @property int $num_used 扫码完成
 * @property int $status 记录状态(0无效,1有效,2完成)
 * @property int $type 操作类型(1订单入库,2直接入库,3调货入库,4订单出库,5直接出库,6调货出库,7关联出库,8直接退货)
 * @property int $vir_need 虚拟总数
 * @property int $vir_used 虚拟完成
 * @property string $code 操作单号
 * @property string $create_time 创建时间
 * @property string $deleted_time 删除时间
 * @property string $ghash 绑定产品
 * @property string $update_time 更新时间
 * @property string $wcode 仓库编号
 * @property PluginWemallGoodsItem $bind_goods
 * @property PluginWemallGoodsItem $goods
 * @property PluginWumaWarehouse $bind_warehouse
 * @property PluginWumaWarehouse $warehouse
 * @class PluginWumaWarehouseOrder
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
     * 关联商品数据.
     */
    public function goods(): HasOne
    {
        return $this->hasOne(PluginWemallGoodsItem::class, 'ghash', 'ghash')->with('bindGoods');
    }

    /**
     * 出库搜索器.
     * @param mixed $query
     */
    public function searchOuterAttr($query)
    {
        $query->whereIn('type', PluginWumaWarehouseOrder::outerTypes);
    }

    /**
     * 入库搜索器.
     * @param mixed $query
     */
    public function searchInterAttr($query)
    {
        $query->whereIn('type', PluginWumaWarehouseOrder::interTypes);
    }

    /**
     * 退货搜索器.
     * @param mixed $query
     */
    public function searchReturnAttr($query)
    {
        $query->whereIn('type', PluginWumaWarehouseOrder::returnTypes);
    }

    /**
     * 绑定产品数据.
     */
    public function bindGoods(): HasOne
    {
        return $this->goods()->bind([
            'gcode' => 'gcode',
            'gname' => 'gname',
            'gunit' => 'gunit',
            'gspec' => 'gspec',
            'gcover' => 'gcover',
        ]);
    }

    /**
     * 关联仓库数据.
     */
    public function warehouse(): HasOne
    {
        return $this->hasOne(PluginWumaWarehouse::class, 'code', 'wcode');
    }

    /**
     * 绑定仓库数据.
     */
    public function bindWarehouse(): HasOne
    {
        return $this->warehouse()->bind([
            'wname' => 'name',
            'wperson' => 'person',
            'wprov' => 'addr_prov',
            'wcity' => 'addr_city',
            'warea' => 'addr_area',
            'wstatus' => 'status',
            'wdeleted' => 'deleted',
        ]);
    }
}
