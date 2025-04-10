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

namespace plugin\wuma\service;

use plugin\wuma\model\PluginWumaWarehouseOrder;
use plugin\wuma\model\PluginWumaWarehouseOrderData;
use plugin\wuma\model\PluginWumaWarehouseOrderDataMins;
use plugin\wuma\model\PluginWumaWarehouseOrderDataNums;
use think\admin\Service;
use think\db\Query;

/**
 * 仓库流转历史清理服务
 * @class RemoveService
 * @package plugin\wuma\service
 */
class RemoveService extends Service
{
    /**
     * 清理入库数据
     * @param array $mins 标签序号
     * @param array $codes 操作单号
     * @return array
     * @throws \think\db\exception\DbException
     */
    public static function inter(array $mins, array $codes = []): array
    {
        return static::withRemove($mins, $codes, PluginWumaWarehouseOrder::interTypes);
    }

    /**
     * 清理出库数据
     * @param array $mins 标签序号
     * @param array $codes 操作单号
     * @return void
     * @throws \think\db\exception\DbException
     */
    public static function outer(array $mins, array $codes = []): array
    {
        return static::withRemove($mins, $codes, PluginWumaWarehouseOrder::outerTypes);
    }

    /**
     * 清理退货数据
     * @param array $mins 标签序号
     * @param array $codes 操作单号
     * @return array
     * @throws \think\db\exception\DbException
     */
    public static function returns(array $mins, array $codes = []): array
    {
        return static::withRemove($mins, $codes, [6]);
    }

    /**
     * 清理仓库历史
     * @param array $mins 标签序号
     * @param array $codes 操作单号
     * @param array $types 操作方式
     * @return array
     * @throws \think\db\exception\DbException
     */
    private static function withRemove(array $mins, array $codes, array $types): array
    {
        $query = PluginWumaWarehouseOrderData::mk()->distinct()->whereIn('type', $types);
        $subdb = PluginWumaWarehouseOrderDataMins::mk()->distinct()->whereIn('type', $types)->whereIn('code', $mins);
        $codes = array_unique(array_merge($codes, $query->whereRaw("id in {$subdb->field('ddid')->buildSql()}")->column('code')));
        $ddids = PluginWumaWarehouseOrderData::mk()->distinct()->whereIn('type', $types)->whereIn('code', $codes)->column('id');
        $xmins = PluginWumaWarehouseOrderDataMins::mk()->distinct()->whereIn('ddid', $ddids)->column('code');
        // 移除仓库流转历史
        PluginWumaWarehouseOrder::mk()->whereIn('code', $codes)->delete();
        PluginWumaWarehouseOrderData::mk()->whereIn('code', $codes)->delete();
        PluginWumaWarehouseOrderDataMins::mk()->whereIn('ddid', $ddids)->delete();
        PluginWumaWarehouseOrderDataNums::mk()->whereIn('ddid', $ddids)->delete();
        return array_unique(array_merge($mins, $xmins));
    }

    /**
     * 请理代理数据
     * @param array $mins
     * @param array $codes
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function agent(array $mins, array $codes = []): array
    {
        $codes = array_unique(array_merge($codes, AgentStockOrderData::mk()->where(static function (Query $query) use ($mins) {
            $db = AgentStockOrderDataMins::mk()->distinct()->whereIn('code', $mins);
            $query->distinct()->whereRaw("id in {$db->field('ddid')->buildSql()}");
        })->column('code')));

        $ddids = AgentStockOrderData::mk()->distinct()->whereIn('code', $codes)->column('id');
        $xmins = AgentStockOrderDataMins::mk()->distinct()->whereIn('id', $ddids)->column('code');
        $auids = AgentStockOrderDataMins::mk()->distinct()->whereIn('code', $xmins)->column('auid');

        AgentStockOrder::mk()->whereIn('code', $codes)->delete();
        AgentStockOrderData::mk()->whereIn('code', $codes)->delete();
        AgentStockOrderDataMins::mk()->whereIn('ddid', $ddids)->delete();
        AgentStockOrderDataNums::mk()->whereIn('ddid', $ddids)->delete();

        // 恢复批次数据及缓存统计
        RelationService::changeRelationLock($xmins, 0);
        foreach ($auids as $id) AgentStock::mk()->sync($id);
        return array_unique(array_merge($mins, $xmins));
    }
}