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
 * 浠撳簱璁㈠崟鏁版嵁妯″瀷.
 *
 * @property int $id
 * @property int $mode 鎿嶄綔鏂瑰紡(1鎵爜鎿嶄綔,2铏氭嫙鎿嶄綔)
 * @property int $number 鏍囩鎬绘暟
 * @property int $status 璁板綍鐘舵€?0鏃犳晥,1鏈夋晥)
 * @property int $type 鎿嶄綔绫诲瀷(1璁㈠崟鍏ュ簱,2鐩存帴鍏ュ簱,3璋冭揣鍏ュ簱,4璁㈠崟鍑哄簱,5鐩存帴鍑哄簱,6璋冭揣鍑哄簱,7鍏宠仈鍑哄簱,8鐩存帴閫€璐?
 * @property string $code 鎿嶄綔鍗曞彿
 * @property string $create_time 鍒涘缓鏃堕棿
 * @property PluginWumaWarehouseOrder $main
 * @class PluginWumaWarehouseOrderData
 */
class PluginWumaWarehouseOrderData extends AbstractPrivate
{
    protected $deleteTime = false;

    /**
     * 鍏宠仈璁㈠崟鏁版嵁.
     */
    public function main(): HasOne
    {
        return $this->hasOne(PluginWumaWarehouseOrder::class, 'code', 'code')->with(['bindGoods', 'bindWarehouse']);
    }

    /**
     * 鍑哄簱鎼滅储鍣?
     * @param mixed $query
     */
    public function searchOuterAttr($query)
    {
        $query->whereIn('type', PluginWumaWarehouseOrder::outerTypes);
    }

    /**
     * 鍏ュ簱鎼滅储鍣?
     * @param mixed $query
     */
    public function searchInterAttr($query)
    {
        $query->whereIn('type', PluginWumaWarehouseOrder::interTypes);
    }

    /**
     * 閫€璐ф悳绱㈠櫒.
     * @param mixed $query
     */
    public function searchReturnAttr($query)
    {
        $query->whereIn('type', PluginWumaWarehouseOrder::returnTypes);
    }
}
