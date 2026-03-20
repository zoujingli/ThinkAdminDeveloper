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

/**
 * 代理库存同步服务。
 * 将仓库出库同步写入代理调货记录，避免历史调用继续引用已删除的旧服务。
 */
class StockService
{
    public static function save(array $body): void
    {
        $auid = intval($body['auid'] ?? 0);
        if ($auid < 1) {
            return;
        }

        $xuid = intval($body['xuid'] ?? 0);
        $code = strval($body['code'] ?? '');
        $ghash = strval($body['ghash'] ?? '');
        if ($code === '' || $ghash === '') {
            return;
        }

        $exists = is_array($body['_exists'] ?? null) ? $body['_exists'] : [];
        $nones = is_array($body['_nones'] ?? null) ? $body['_nones'] : [];
        $maps = is_array($body['_maps'] ?? null) ? $body['_maps'] : [];
        $unis = is_array($body['_unis'] ?? null) ? $body['_unis'] : [];
        $mins = array_keys($exists + $nones);
        $count = intval($body['_count'] ?? count($mins));
        $numCount = count($exists);
        $virCount = count($nones);
        $mode = $numCount < 1 && $virCount > 0 ? 2 : 1;

        PluginWumaSalesOrder::mk()->save([
            'auid' => $auid,
            'xuid' => $xuid,
            'code' => $code,
            'mode' => $mode,
            'ghash' => $ghash,
            'num_need' => $numCount,
            'num_count' => $numCount,
            'vir_need' => $virCount,
            'vir_count' => $virCount,
            'status' => 2,
        ]);

        $dataModel = PluginWumaSalesOrderData::mk();
        $dataModel->save([
            'auid' => $auid,
            'xuid' => $xuid,
            'code' => $code,
            'mode' => $mode,
            'status' => 1,
            'number' => $count,
        ]);
        $ddid = intval($dataModel->getAttr('id'));

        $itemMins = [];
        foreach ($nones as $min => $_code) {
            $itemMins[] = ['ddid' => $ddid, 'auid' => $auid, 'ghash' => $ghash, 'code' => $min, 'mode' => 2, 'stock' => 1, 'status' => 1, 'status_time' => date('Y-m-d H:i:s')];
        }
        foreach ($exists as $min => $_code) {
            $itemMins[] = ['ddid' => $ddid, 'auid' => $auid, 'ghash' => $ghash, 'code' => $min, 'mode' => 1, 'stock' => 1, 'status' => 1, 'status_time' => date('Y-m-d H:i:s')];
        }
        foreach (array_chunk($itemMins, 1000) as $items) {
            if ($items !== []) {
                PluginWumaSalesOrderDataMins::mk()->insertAll($items);
            }
        }

        $itemNums = [];
        foreach ($unis as $code) {
            $attr = explode('#', strval($maps[$code] ?? ''));
            if (count($attr) === 3) {
                $itemNums[] = ['uuid' => 0, 'ddid' => $ddid, 'code' => $attr[0], 'type' => $attr[1], 'count' => intval($attr[2])];
            }
        }
        if ($itemNums !== []) {
            PluginWumaSalesOrderDataNums::mk()->insertAll($itemNums);
        }

        PluginWumaSalesUserStock::sync($auid);
        if ($xuid > 0 && $xuid !== $auid) {
            PluginWumaSalesUserStock::sync($xuid);
        }
    }
}
