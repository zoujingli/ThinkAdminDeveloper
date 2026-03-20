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

namespace plugin\wuma\service;

use plugin\wuma\model\PluginWumaSalesOrder;
use plugin\wuma\model\PluginWumaSalesOrderData;
use plugin\wuma\model\PluginWumaSalesOrderDataMins;
use plugin\wuma\model\PluginWumaSalesOrderDataNums;
use plugin\wuma\model\PluginWumaSalesUserStock;
use plugin\wuma\model\PluginWumaWarehouseOrder;
use plugin\wuma\model\PluginWumaWarehouseOrderData;
use plugin\wuma\model\PluginWumaWarehouseOrderDataMins;
use plugin\wuma\model\PluginWumaWarehouseOrderDataNums;
use think\admin\Service;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\db\Query;

/**
 * 仓库流转历史清理服务
 * @class RemoveService
 */
class RemoveService extends Service
{
    /**
     * 清理入库数据.
     * @param array $mins 标签序号
     * @param array $codes 操作单号
     * @throws DbException
     */
    public static function inter(array $mins, array $codes = []): array
    {
        return self::withRemove($mins, $codes, PluginWumaWarehouseOrder::interTypes);
    }

    /**
     * 清理出库数据.
     * @param array $mins 标签序号
     * @param array $codes 操作单号
     * @throws DbException
     */
    public static function outer(array $mins, array $codes = []): array
    {
        return self::withRemove($mins, $codes, PluginWumaWarehouseOrder::outerTypes);
    }

    /**
     * 清理退货数据.
     * @param array $mins 标签序号
     * @param array $codes 操作单号
     * @throws DbException
     */
    public static function returns(array $mins, array $codes = []): array
    {
        return self::withRemove($mins, $codes, [6]);
    }

    /**
     * 请理代理数据.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function agent(array $mins, array $codes = []): array
    {
        $codes = array_unique(array_merge($codes, PluginWumaSalesOrderData::mk()->where(static function (Query $query) use ($mins) {
            $db = PluginWumaSalesOrderDataMins::mk()->distinct()->whereIn('code', $mins);
            $query->distinct()->whereRaw("id in {$db->field('ddid')->buildSql()}");
        })->column('code')));

        $ddids = PluginWumaSalesOrderData::mk()->distinct()->whereIn('code', $codes)->column('id');
        $xmins = PluginWumaSalesOrderDataMins::mk()->distinct()->whereIn('ddid', $ddids)->column('code');
        $auids = PluginWumaSalesOrderDataMins::mk()->distinct()->whereIn('code', $xmins)->column('auid');

        PluginWumaSalesOrder::mk()->whereIn('code', $codes)->delete();
        PluginWumaSalesOrderData::mk()->whereIn('code', $codes)->delete();
        PluginWumaSalesOrderDataMins::mk()->whereIn('ddid', $ddids)->delete();
        PluginWumaSalesOrderDataNums::mk()->whereIn('ddid', $ddids)->delete();

        // 恢复批次数据及缓存统计
        RelationService::changeRelationLock($xmins, 0);
        foreach ($auids as $id) {
            PluginWumaSalesUserStock::sync($id);
        }
        return array_unique(array_merge($mins, $xmins));
    }

    /**
     * 清理仓库历史.
     * @param array $mins 标签序号
     * @param array $codes 操作单号
     * @param array $types 操作方式
     * @throws DbException
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
}
