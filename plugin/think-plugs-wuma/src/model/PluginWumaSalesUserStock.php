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

use plugin\wemall\model\PluginWemallGoodsItem;
use think\Model;
use think\model\relation\HasOne;

/**
 * Class plugin\wuma\model\PluginWumaSalesUserStock
 *
 * @property int $auid 经销编号
 * @property int $id
 * @property int $num_count 累计出货
 * @property int $num_total 累计库存
 * @property int $vir_count 虚拟出货
 * @property int $vir_total 虚拟库存
 * @property string $ghash 商品哈唏
 * @property-read \plugin\wemall\model\PluginWemallGoodsItem $bind_goods
 * @property-read \plugin\wemall\model\PluginWemallGoodsItem $goods
 * @property-read \plugin\wuma\model\PluginWumaSalesUser $agent
 */
class PluginWumaSalesUserStock extends AbstractPrivate
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
     * 同步记录代理库存
     * @param mixed $auid 代理编号
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function sync($auid)
    {
        $stock = [];

        $fields = 'auid,ghash,sum(num_count) num_total,sum(vir_count) vir_total,0 num_count,0 vir_count';

        // 统计仓库出库数据
        $where = ['auid' => $auid, 'status' => 2, 'deleted' => 0];
        PluginWumaSalesOrder::mk()->where($where)->whereRaw('auid<>xuid')->field($fields)->group('ghash')->select()->map(function (Model $total) use (&$stock) {
            $stock[$total->getAttr('ghash')] = $total->toArray();
        });

        // 统计仓库入库数据
        $where = ['xuid' => $auid, 'status' => 2, 'deleted' => 0];
        PluginWumaSalesOrder::mk()->where($where)->whereRaw('auid<>xuid')->field($fields)->group('ghash')->select()->map(function (Model $total) use (&$stock) {
            if (isset($stock[$key = $total->getAttr('ghash')])) {
                $stock[$key]['num_count'] = $total->getAttr('num_total') ?? 0;
                $stock[$key]['vir_count'] = $total->getAttr('vir_total') ?? 0;
            }
        });

        // 清理并写入数据
        static::mk()->where(['auid' => $auid])->delete();
        static::mk()->insertAll($stock);
    }
}