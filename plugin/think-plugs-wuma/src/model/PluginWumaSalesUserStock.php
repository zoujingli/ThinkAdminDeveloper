<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdminDeveloper
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed: https://mit-license.org
 * | Disclaimer: https://thinkadmin.top/disclaimer
 * | Vip Rights: https://thinkadmin.top/vip-introduce
 * +----------------------------------------------------------------------
 * | Gitee Repository: https://gitee.com/zoujingli/ThinkAdmin
 * | Github Repository: https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace plugin\wuma\model;

use plugin\wemall\model\PluginWemallGoodsItem;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Model;
use think\model\relation\HasOne;

/**
 * Class plugin\wuma\model\PluginWumaSalesUserStock.
 *
 * @property int $auid 经销编号
 * @property int $id
 * @property int $num_count 累计出货
 * @property int $num_total 累计库存
 * @property int $vir_count 虚拟出货
 * @property int $vir_total 虚拟库存
 * @property string $ghash 商品哈唏
 * @property PluginWemallGoodsItem $bind_goods
 * @property PluginWemallGoodsItem $goods
 * @property PluginWumaSalesUser $agent
 */
class PluginWumaSalesUserStock extends AbstractPrivate
{
    /**
     * 关联代理数据.
     */
    public function agent(): HasOne
    {
        return $this->hasOne(PluginWumaSalesUser::class, 'id', 'auid');
    }

    /**
     * 关联商品数据.
     */
    public function goods(): HasOne
    {
        $relation = $this->hasOne(PluginWemallGoodsItem::class, 'ghash', 'ghash');
        $relation->with('bindGoods');
        return $relation;
    }

    /**
     * 绑定商品数据.
     */
    public function bindGoods(): HasOne
    {
        return $this->goods()->bind([
            'gunit' => 'gunit',
            'gcode' => 'gcode',
            'gname' => 'gname',
            'gspec' => 'gspec',
            'gcover' => 'gcover',
            'gstatus' => 'gstatus',
            'gdelete_time' => 'gdelete_time',
        ]);
    }

    /**
     * 同步记录代理库存.
     * @param mixed $auid 代理编号
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function sync($auid)
    {
        $stock = [];

        $fields = 'auid,ghash,sum(num_count) num_total,sum(vir_count) vir_total,0 num_count,0 vir_count';

        // 统计仓库出库数据
        $where = ['auid' => $auid, 'status' => 2];
        PluginWumaSalesOrder::mk()->where($where)->whereRaw('auid<>xuid')->field($fields)->group('ghash')->select()->map(function (Model $total) use (&$stock) {
            $stock[$total->getAttr('ghash')] = $total->toArray();
        });

        // 统计仓库入库数据
        $where = ['xuid' => $auid, 'status' => 2];
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
