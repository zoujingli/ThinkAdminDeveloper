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
use think\admin\Model;
use think\model\relation\HasOne;

/**
 * 仓库库存模型
 *
 * @property int $id
 * @property int $num_count 扫码完成
 * @property int $num_total 扫码总数
 * @property int $vir_count 虚拟完成
 * @property int $vir_total 虚拟总数
 * @property string $ghash 商品规格
 * @property string $wcode 仓库编号
 * @property-read \plugin\wemall\model\PluginWemallGoodsItem $bind_goods
 * @property-read \plugin\wemall\model\PluginWemallGoodsItem $goods
 * @property-read \plugin\wuma\model\PluginWumaWarehouse $bind_warehouse
 * @property-read \plugin\wuma\model\PluginWumaWarehouse $warehouse
 * @class PluginWumaWarehouseStock
 * @package plugin\wuma\model
 */
class PluginWumaWarehouseStock extends Model
{

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

    /**
     * 关联仓库数据
     * @return \think\model\relation\HasOne
     */
    public function warehouse(): HasOne
    {
        return $this->hasOne(PluginWumaWarehouse::class, 'code', 'wcode');
    }

    /**
     * 绑定库数据
     * @return \think\model\relation\HasOne
     */
    public function bindWarehouse(): HasOne
    {
        return $this->warehouse()->bind([
            'wname'    => 'name',
            'wstatus'  => 'status',
            'wdeleted' => 'deleted'
        ]);
    }

    /**
     * 更新库存统计
     * @param string $wcode 仓库编号
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function sync(string $wcode)
    {
        [$map, $stock] = [['wcode' => $wcode], []];
        $fields = 'wcode,ghash,sum(num_used) num_total,sum(vir_used) vir_total,0 num_count,0 vir_count';

        // 仓库入库统计
        $inter = PluginWumaWarehouseOrder::mk()->where($map)->withSearch('inter')->field($fields)->group('ghash');
        foreach ($inter->cursor() as $total) $stock[$total->getAttr('ghash')] = $total->toArray();

        // 仓库出库统计
        $outer = PluginWumaWarehouseOrder::mk()->where($map)->withSearch('outer')->field($fields)->group('ghash');
        foreach ($outer->cursor() as $total) if (isset($stock[$key = $total->getAttr('ghash')])) {
            $stock[$key]['num_count'] = $total->getAttr('num_total');
            $stock[$key]['vir_count'] = $total->getAttr('vir_total');
        }

        // 清理并写入数据
        static::mk()->where($map)->delete();
        static::mk()->insertAll(array_values($stock));
    }
}